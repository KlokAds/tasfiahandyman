<?php

namespace Tests\Feature;

use App\Models\BlogDetail;
use App\Models\ContentAiJob;
use App\Models\ServiceDetail;
use App\Models\SiteSetting;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

/** Content AI: only the owner account, a draft is saved (never published), captions and checks are kept. */
class ContentAiTest extends TestCase
{
    use RefreshDatabase;

    private ContentAiFakeClaude $claude;

    protected function setUp(): void
    {
        parent::setUp();
        $this->claude = new ContentAiFakeClaude;
        $this->app->instance('content_ai.transport', $this->claude);
        SiteSetting::putMany(['content_ai.anthropic_key' => Crypt::encryptString('sk-ant-test-1234')]);
        @mkdir(public_path('Admin/Blog/Details'), 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (ContentAiJob::pluck('photos')->flatten() as $path) {
            @unlink(public_path($path)); // only the test's own uploaded photos
        }
        parent::tearDown();
    }

    private function owner(): User
    {
        return User::factory()->create(['email' => 'mujxaid@gmail.com', 'is_active' => true])->assignRole('super-admin');
    }

    private function answer(): array
    {
        return [
            'title' => 'Sliding Door Roller Replacement in a Tampines HDB',
            'focus_keyword' => 'sliding door roller replacement',
            'meta_description' => 'How we fixed a kitchen sliding door that was hard to push: worn rollers replaced and the track cleaned in a Tampines HDB flat.',
            'excerpt' => 'A kitchen sliding door that drags or jumps off its track usually has worn rollers; replacing them fixes it.',
            'body_html' => '<p>Intro.</p><h2>What was wrong?</h2><p>Worn rollers.</p><h2>What we did</h2><ul><li>Replaced 2 rollers</li></ul><h2>When to call</h2><p>Free site visit.</p>',
            'faqs' => [['question' => 'Is the site visit free?', 'answer' => 'Yes.'], ['question' => 'When do you visit?', 'answer' => '8am to 10pm.'], ['question' => 'Can I send photos?', 'answer' => 'Yes, on WhatsApp.']],
            'image_alts' => ['Worn sliding door roller', 'New roller fitted'],
            'facebook' => 'Facebook caption', 'instagram' => 'Instagram caption #handyman', 'tiktok' => 'TikTok caption',
            'privacy_warnings' => ['Photo 2: unit number on the door'],
            'questions' => ['Which roller brand was used?'],
        ];
    }

    private function send(User $user, array $extra = [])
    {
        return $this->actingAs($user)->post('/admin/content-ai', $extra + [
            'service_id' => ServiceDetail::create(['name' => 'Sliding Door Roller Replacement Service', 'desc' => 'x', 'is_active' => true])->id,
            'area' => 'Tampines',
            'note' => 'Kitchen sliding door hard to push, rollers worn flat. Replaced 2 rollers and cleaned the track.',
            'photos' => [UploadedFile::fake()->image('a.jpg', 400, 300), UploadedFile::fake()->image('b.jpg', 400, 300)],
        ]);
    }

    public function test_only_the_owner_account_can_open_and_use_it(): void
    {
        $other = User::factory()->create(['email' => 'team@example.com', 'is_active' => true])->assignRole('super-admin');

        $this->actingAs($other)->get('/admin/content-ai')->assertNotFound();
        $this->actingAs($other)->post('/admin/content-ai', [])->assertNotFound();
        $this->assertFalse($this->actingAs($other)->get('/admin/dashboard')->viewData('page')['props']['admin']['can']['content_ai']);

        $owner = $this->owner();
        $this->actingAs($owner)->get('/admin/content-ai')->assertOk();
        $this->assertTrue($this->actingAs($owner)->get('/admin/dashboard')->viewData('page')['props']['admin']['can']['content_ai']);
    }

    public function test_photos_and_a_note_become_a_draft_article_and_captions(): void
    {
        $this->claude->reply($this->answer());

        $this->send($this->owner())->assertSessionHas('success');

        $blog = BlogDetail::sole();
        $this->assertSame(BlogDetail::DRAFT, $blog->status, 'never published by itself');
        $this->assertFalse($blog->is_active);
        $this->assertSame('sliding-door-roller-replacement-in-a-tampines-hdb', $blog->slug);
        $this->assertStringContainsString('alt="New roller fitted"', $blog->desc, 'the second photo is in the article');
        $this->assertCount(3, $blog->faqs);
        $this->assertNotNull($blog->image);

        $job = ContentAiJob::sole();
        $this->assertSame('done', $job->status);
        $this->assertSame($blog->id, $job->blog_id);
        $this->assertSame('TikTok caption', $job->social['tiktok']);
        $this->assertSame(['Photo 2: unit number on the door'], $job->checks['privacy']);
        $this->assertGreaterThan(0, (float) $job->cost);

        // The request: the model from config, both photos as images, and the "no prices" rule (no approved prices yet).
        $req = $this->claude->requests[0];
        $this->assertSame('claude-sonnet-5-5', $req['model']);
        $this->assertCount(2, collect($req['messages'][0]['content'])->where('type', 'image'));
        $this->assertStringContainsString('Approved price list: none yet', json_encode($req['messages']));
        $this->assertSame('json_schema', $req['output_config']['format']['type']);
    }

    public function test_the_daily_limit_stops_mass_production(): void
    {
        config(['content_ai.daily_limit' => 1]);
        $this->claude->reply($this->answer());
        $owner = $this->owner();

        $this->send($owner)->assertSessionHas('success');
        $this->send($owner)->assertSessionHas('error');
        $this->assertSame(1, BlogDetail::count());
    }

    public function test_an_incomplete_answer_saves_no_article(): void
    {
        $this->claude->reply(['title' => 'x'] + array_fill_keys(['body_html'], ''), 'max_tokens');

        $this->send($this->owner())->assertSessionHas('error');
        $this->assertSame(0, BlogDetail::count());
        $this->assertSame('failed', ContentAiJob::sole()->status);
    }
}

/** Answers like the Claude API, one queued answer per request, and keeps what was sent. */
class ContentAiFakeClaude implements ClientInterface
{
    public array $requests = [];

    private array $queue = [];

    public function reply(array $json, string $stop = 'end_turn'): void
    {
        $this->queue[] = [$json, $stop];
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = json_decode((string) $request->getBody(), true);
        [$json, $stop] = array_shift($this->queue) ?? [[], 'end_turn'];

        return new Response(200, ['content-type' => 'application/json'], json_encode([
            'id' => 'msg_1', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-sonnet-5-5',
            'content' => [['type' => 'text', 'text' => json_encode($json)]],
            'stop_reason' => $stop, 'stop_sequence' => null,
            'usage' => ['input_tokens' => 5000, 'output_tokens' => 1500],
        ]));
    }
}
