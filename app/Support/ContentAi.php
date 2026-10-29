<?php

namespace App\Support;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use App\Models\BlogDetail;
use App\Models\ContentAiJob;
use App\Models\Price;
use App\Models\ServiceDetail;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Content AI: photos of a finished job and a short note become a draft article (saved as a draft in
 * Articles, never published by itself) and captions for Facebook, Instagram and TikTok.
 *
 * Everything the AI writes must come from the photos, the note and the facts given here; it is told
 * never to invent prices, numbers, reviews or claims, and to flag anything private in the photos.
 */
class ContentAi
{
    public static function allowed(?User $user): bool
    {
        return $user !== null && in_array(Str::lower($user->email), array_map('strtolower', config('content_ai.emails')), true);
    }

    public static function key(): ?string
    {
        return ApiKeys::value('anthropic');
    }

    public static function usedToday(): int
    {
        return ContentAiJob::where('created_at', '>=', now()->startOfDay())->where('status', 'done')->count();
    }

    /**
     * Writes the draft and captions for one job.
     *
     * @param array<int, array{path: string, mime: string}> $photos stored photos (public paths)
     * @param Client|null $client for tests
     */
    public static function run(User $user, ?ServiceDetail $service, ?string $area, string $note, array $photos, ?Client $client = null): ContentAiJob
    {
        $job = new ContentAiJob([
            'user_id' => $user->id, 'service_id' => $service?->id, 'area' => $area, 'note' => $note,
            'photos' => array_column($photos, 'path'),
        ]);

        $key = self::key();
        if (!$client && !$key) {
            throw new RuntimeException('No Anthropic API key is saved yet. Add it in System → Settings → API keys.');
        }
        // Tests bind a fake transport here instead of calling the API.
        $client ??= new Client(apiKey: $key, requestOptions: array_filter(['transporter' => app()->bound('content_ai.transport') ? app('content_ai.transport') : null]));

        $content = [];
        foreach ($photos as $i => $p) {
            $content[] = ['type' => 'text', 'text' => 'Photo ' . ($i + 1) . ':'];
            $content[] = ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $p['mime'], 'data' => base64_encode((string) file_get_contents(public_path($p['path'])))]];
        }
        $content[] = ['type' => 'text', 'text' => self::brief($service, $area, $note)];

        try {
            $r = $client->messages->create(
                maxTokens: 12000,
                messages: [['role' => 'user', 'content' => $content]],
                model: config('content_ai.model'),
                system: self::system(),
                outputConfig: ['effort' => 'medium', 'format' => ['type' => 'json_schema', 'schema' => self::schema()]],
            );
        } catch (APIStatusException $e) {
            throw new RuntimeException(match ((int) $e->status) {
                401, 403 => 'The Anthropic API key does not work. Save a new one in System → Settings → API keys.',
                429 => 'The AI is busy or the account has no credit left. Try again in a minute.',
                default => 'The AI could not answer: ' . $e->getMessage(),
            });
        } catch (APIConnectionException) {
            throw new RuntimeException('The AI could not be reached. Try again in a minute.');
        }

        $job->input_tokens = (int) $r->usage->inputTokens;
        $job->output_tokens = (int) $r->usage->outputTokens;
        $job->cost = round($job->input_tokens / 1e6 * config('content_ai.price.in') + $job->output_tokens / 1e6 * config('content_ai.price.out'), 4);

        $text = collect(json_decode(json_encode($r->content), true) ?: [])->where('type', 'text')->pluck('text')->implode('');
        $out = json_decode($text, true);
        if ((string) $r->stopReason !== 'end_turn' || !is_array($out) || empty($out['title']) || empty($out['body_html'])) {
            $job->fill(['status' => 'failed', 'error' => 'The AI answer was incomplete (' . $r->stopReason . '). Try again with fewer photos or a shorter note.'])->save();

            return $job;
        }

        $blog = self::saveDraft($user, $service, $out, $photos);
        $job->fill([
            'status' => 'done',
            'blog_id' => $blog->id,
            'social' => ['facebook' => $out['facebook'], 'instagram' => $out['instagram'], 'tiktok' => $out['tiktok']],
            'checks' => ['privacy' => array_values($out['privacy_warnings']), 'questions' => array_values($out['questions'])],
        ])->save();

        return $job;
    }

    private static function system(): string
    {
        $brand = SiteSetting::get('business.brand_name') ?: config('app.name');
        $banned = implode('; ', WritingCheck::AI_PHRASES);

        return <<<TXT
You write for {$brand}, a home repair company in Singapore. You get photos of one real job the team just finished and a short note from the team. You write a helpful article about that job for the website, and short social media captions.

Facts you may use:
- What you can see in the photos and what the note says.
- Site visits are free. Visits are between 8am and 10pm. Customers can send photos on WhatsApp for a first idea of the work.
- Prices only from the approved price list in the brief; if there is none, do not state any price or price range.

Never:
- invent prices, years in business, numbers of jobs, warranties, brands, materials, time taken, customer names, quotes or reviews that are not in the note or the brief;
- name the customer, the exact address, block or unit number, or anything that identifies the home;
- copy the note word for word into many places; write it up properly.

How to write:
- Plain Singapore English, short sentences, practical. Write like the technician explaining the job to a neighbour, not like an advert.
- The article: a 1–2 sentence direct answer at the top (the excerpt), then sections with H2 headings (at least one phrased as a question people ask), a bullet or numbered list where it helps, what the problem was, what was done, signs a reader may have the same problem, and when to call. 500–900 words. Mention HDB/condo or the area only if the note says so.
- Use <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong> only. No <h1>, no images, no links, no inline styles.
- 3–5 FAQs with short complete answers, only from the facts above.
- Image alt texts: one per photo, describing what is in that photo.
- Captions: Facebook (60–120 words), Instagram (40–90 words plus 5–8 relevant hashtags), TikTok (1–2 short lines plus 3–5 hashtags). Each ends with how to reach us (WhatsApp, free site visit). No emojis in the article; at most 2 in each caption.
- Do not use these phrases: {$banned}.

Checks:
- privacy_warnings: list anything visible in a photo that should be hidden before posting (faces, unit or block numbers, letters or parcels with addresses, car plates, family photos). Say which photo. Empty when none.
- questions: facts that would make the article better and that the team should confirm (e.g. "Which brand of roller was used?"). Empty when none.
TXT;
    }

    private static function brief(?ServiceDetail $service, ?string $area, string $note): string
    {
        $prices = $service ? Price::where('service_id', $service->id)->where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn ($p) => "- {$p->item}: S\${$p->price_from}" . ($p->price_to ? "–S\${$p->price_to}" : '+') . ($p->unit ? " {$p->unit}" : ''))->implode("\n") : '';

        $searches = '';
        try {
            $words = $service ? collect(preg_split('/[^a-z]+/', Str::lower($service->name)))->filter(fn ($w) => strlen($w) > 3)->values() : collect();
            $queries = collect(\App\Support\Google\SearchConsole::report()['queries'] ?? [])->pluck('key')
                ->filter(fn ($q) => $words->contains(fn ($w) => str_contains($q, $w)))->take(10)->implode(', ');
            $searches = $queries;
        } catch (\Throwable) {
            // Search Console is optional.
        }

        return implode("\n", array_filter([
            'Brief from the team:',
            $service ? "Service: {$service->name}" : 'Service: not chosen (work it out from the photos and note)',
            $area ? "Area in Singapore: {$area}" : null,
            "Note: {$note}",
            $prices !== '' ? "Approved price list for this service:\n{$prices}" : 'Approved price list: none yet, so state no prices.',
            $searches !== '' ? "Searches people used to find this website (use the most fitting one as the focus keyword if it matches the job): {$searches}" : null,
        ]));
    }

    private static function schema(): array
    {
        $str = ['type' => 'string'];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['title', 'focus_keyword', 'meta_description', 'excerpt', 'body_html', 'faqs', 'image_alts', 'facebook', 'instagram', 'tiktok', 'privacy_warnings', 'questions'],
            'properties' => [
                'title' => ['type' => 'string', 'description' => '30–60 characters, focus keyword first.'],
                'focus_keyword' => $str,
                'meta_description' => ['type' => 'string', 'description' => '70–160 characters.'],
                'excerpt' => ['type' => 'string', 'description' => 'The direct answer, 1–2 sentences.'],
                'body_html' => $str,
                'faqs' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['question', 'answer'], 'properties' => ['question' => $str, 'answer' => $str]]],
                'image_alts' => ['type' => 'array', 'items' => $str],
                'facebook' => $str,
                'instagram' => $str,
                'tiktok' => $str,
                'privacy_warnings' => ['type' => 'array', 'items' => $str],
                'questions' => ['type' => 'array', 'items' => $str],
            ],
        ];
    }

    /** The article as a draft in Articles, with the photos in it, for the owner to check, edit and publish. */
    private static function saveDraft(User $user, ?ServiceDetail $service, array $out, array $photos): BlogDetail
    {
        $alts = array_values($out['image_alts']);
        $figures = collect($photos)->slice(1)->values()->map(fn ($p, $i) => '<figure><img src="/' . e(ltrim($p['path'], '/')) . '" alt="' . e($alts[$i + 1] ?? $out['title']) . '"></figure>')->all();

        // The extra photos go after the first section, so the article opens with the answer.
        $body = (string) $out['body_html'];
        if ($figures) {
            $pos = stripos($body, '<h2', 1);
            $pos = $pos === false ? strlen($body) : (stripos($body, '<h2', $pos + 3) ?: strlen($body));
            $body = substr($body, 0, $pos) . implode("\n", $figures) . substr($body, $pos);
        }

        $title = Str::limit(trim($out['title']), 250, '');
        $slug = Str::slug($title);
        if (BlogDetail::where('slug', $slug)->exists()) {
            $slug .= '-' . now()->format('ymd');
        }

        $blog = new BlogDetail([
            'name' => $title,
            'slug' => $slug,
            'desc' => $body,
            'excerpt' => Str::limit(trim($out['excerpt']), 500, ''),
            'meta_title' => $title,
            'meta_desc' => Str::limit(trim($out['meta_description']), 500, ''),
            'focus_keyword' => Str::limit(trim($out['focus_keyword']), 120, ''),
            'primary_service_id' => $service?->id,
            'image' => $photos[0]['path'] ?? null,
            'status' => BlogDetail::DRAFT,
            'auth_name' => $user->name,
            'btn_name' => 'Read More',
        ]);
        $blog->author_id = $user->id;
        $blog->review_note = 'Written by Content AI from job photos. Check every fact before publishing.';
        $blog->save();
        $blog->syncFaqs(collect($out['faqs'])->map(fn ($f) => ['question' => $f['question'], 'answer' => $f['answer']])->all());

        return $blog;
    }
}
