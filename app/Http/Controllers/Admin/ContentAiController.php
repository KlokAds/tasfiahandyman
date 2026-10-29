<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentAiJob;
use App\Models\ServiceDetail;
use App\Support\ContentAi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/** Admin → Content AI: job photos and a note in, a draft article and social captions out. Owner only. */
class ContentAiController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(ContentAi::allowed($request->user()), 404);

        return Inertia::render('Admin/ContentAi/Index', [
            'services' => ServiceDetail::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'jobs' => ContentAiJob::with(['blog:id,name,slug,status', 'service:id,name'])->latest()->limit(30)->get()
                ->map(fn ($j) => [
                    'id' => $j->id, 'created_at' => $j->created_at, 'status' => $j->status, 'error' => $j->error,
                    'service' => $j->service?->name, 'area' => $j->area, 'note' => $j->note, 'photos' => $j->photos,
                    'blog' => $j->blog ? ['id' => $j->blog->id, 'name' => $j->blog->name, 'status' => $j->blog->status, 'slug' => $j->blog->slug] : null,
                    'social' => $j->social, 'checks' => $j->checks, 'cost' => (float) $j->cost,
                ]),
            'hasKey' => (bool) ContentAi::key(),
            'usedToday' => ContentAi::usedToday(),
            'dailyLimit' => config('content_ai.daily_limit'),
            'maxPhotos' => config('content_ai.max_photos'),
            'monthCost' => (float) ContentAiJob::where('created_at', '>=', now()->startOfMonth())->sum('cost'),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(ContentAi::allowed($request->user()), 404);
        $max = config('content_ai.max_photos');
        $data = $request->validate([
            'service_id' => 'nullable|exists:service_details,id',
            'area' => 'nullable|string|max:100',
            'note' => 'required|string|min:15|max:3000',
            'photos' => "required|array|min:1|max:{$max}",
            'photos.*' => 'image|mimes:jpeg,png,webp|max:6144',
        ], ['note.min' => 'Write a little more: what was wrong and what you did.']);

        if (ContentAi::usedToday() >= config('content_ai.daily_limit')) {
            return back()->with('error', 'Today\'s limit of ' . config('content_ai.daily_limit') . ' drafts is reached. Publishing many articles at once can hurt the website in Google; try again tomorrow.');
        }

        // The page already shrank the photos and removed their location data; they are kept with the article.
        $photos = [];
        foreach ($request->file('photos') as $file) {
            $name = 'job-' . now()->format('ymd-His') . '-' . Str::lower(Str::random(6)) . '.' . ($file->extension() === 'png' ? 'png' : ($file->extension() === 'webp' ? 'webp' : 'jpg'));
            $photos[] = ['path' => $file->storeAs('Admin/Blog/Details', $name, 'uploads'), 'mime' => $file->getMimeType()];
        }

        @set_time_limit(300);
        try {
            $job = ContentAi::run($request->user(), isset($data['service_id']) ? ServiceDetail::find($data['service_id']) : null, $data['area'] ?? null, $data['note'], $photos);
        } catch (\RuntimeException $e) {
            ContentAiJob::create([
                'user_id' => $request->user()->id, 'service_id' => $data['service_id'] ?? null, 'area' => $data['area'] ?? null,
                'note' => $data['note'], 'photos' => array_column($photos, 'path'), 'status' => 'failed', 'error' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }

        return $job->status === 'done'
            ? back()->with('success', 'Draft article and captions are ready. Check the facts and the photos, then publish from Articles.')
            : back()->with('error', $job->error);
    }
}
