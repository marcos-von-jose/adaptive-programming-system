<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competency;
use App\Models\LearningContent;
use App\Models\ProgrammingLanguage;
use App\Models\SystemLog;
use App\Models\SystemSetting;
use App\Support\YouTubeVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ContentController extends Controller
{
    public function index(Request $request): View
    {
        $contents = LearningContent::query()
            ->with(['competency', 'programmingLanguage', 'author'])
            ->when($this->isInstructor(), fn ($query) => $query->where('created_by', auth()->id()))
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate((int) SystemSetting::valueFor('items_per_page', 10))
            ->withQueryString();

        return view('admin.content.index', compact('contents'));
    }

    public function create(): View
    {
        return view('admin.content.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateContent($request);
        $validated['slug'] = $this->uniqueSlug($validated['title']);
        $validated['created_by'] = $request->user()->id;
        $validated['published_at'] = $validated['status'] === 'published' ? now() : null;
        [$validated, $newVideoPath] = $this->videoAttributes($request, $validated);

        try {
            $content = LearningContent::create($validated);
        } catch (Throwable $exception) {
            if ($newVideoPath) {
                Storage::disk('local')->delete($newVideoPath);
            }

            throw $exception;
        }

        SystemLog::record('content.created', "Created learning content: {$content->title}.", ['content_id' => $content->id]);

        return redirect()->route($this->routeName('content.index'))->with('success', 'Learning content created successfully.');
    }

    public function edit(LearningContent $content): View
    {
        $this->authorizeOwner($content);
        return view('admin.content.edit', array_merge($this->formData(), compact('content')));
    }

    public function update(Request $request, LearningContent $content): RedirectResponse
    {
        $this->authorizeOwner($content);
        $validated = $this->validateContent($request);

        if ($content->title !== $validated['title']) {
            $validated['slug'] = $this->uniqueSlug($validated['title'], $content->id);
        }

        $validated['published_at'] = $validated['status'] === 'published'
            ? ($content->published_at ?? now())
            : null;
        [$validated, $newVideoPath] = $this->videoAttributes($request, $validated, $content);
        $oldVideoPath = $content->video_path;

        try {
            $content->update($validated);
        } catch (Throwable $exception) {
            if ($newVideoPath) {
                Storage::disk('local')->delete($newVideoPath);
            }

            throw $exception;
        }

        if ($oldVideoPath && $oldVideoPath !== $content->video_path) {
            Storage::disk('local')->delete($oldVideoPath);
        }

        SystemLog::record('content.updated', "Updated learning content: {$content->title}.", ['content_id' => $content->id]);

        return redirect()->route($this->routeName('content.index'))->with('success', 'Learning content updated successfully.');
    }

    public function destroy(LearningContent $content): RedirectResponse
    {
        $this->authorizeOwner($content);
        $title = $content->title;
        $id = $content->id;
        $videoPath = $content->video_path;
        $content->delete();
        $content->learningProgress()->delete();
        if ($videoPath) {
            Storage::disk('local')->delete($videoPath);
        }
        SystemLog::record('content.deleted', "Deleted learning content: {$title}.", ['content_id' => $id]);

        return back()->with('success', 'Learning content deleted successfully.');
    }

    private function validateContent(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string'],
            'competency_id' => ['nullable', 'exists:competencies,id'],
            'programming_language_id' => ['nullable', 'exists:programming_languages,id'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'video_upload' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg', 'max:102400'],
            'youtube_url' => ['nullable', 'url', 'max:2048'],
            'remove_video' => ['nullable', 'boolean'],
        ]);
    }

    private function videoAttributes(Request $request, array $validated, ?LearningContent $content = null): array
    {
        $upload = $request->file('video_upload');
        $youtubeUrl = $validated['youtube_url'] ?? null;
        $removeVideo = (bool) ($validated['remove_video'] ?? false);

        if ($upload && $youtubeUrl) {
            throw ValidationException::withMessages(['youtube_url' => 'Choose a video upload or a YouTube link, not both.']);
        }

        if ($removeVideo && ($upload || $youtubeUrl)) {
            throw ValidationException::withMessages(['remove_video' => 'To remove the video, leave the upload and YouTube link empty.']);
        }

        $youtubeId = $youtubeUrl ? YouTubeVideo::idFromUrl($youtubeUrl) : null;
        if ($youtubeUrl && ! $youtubeId) {
            throw ValidationException::withMessages(['youtube_url' => 'Enter a valid YouTube video link.']);
        }

        unset($validated['video_upload'], $validated['youtube_url'], $validated['remove_video']);

        $newVideoPath = null;
        if ($upload) {
            $newVideoPath = $upload->store('lesson-videos', 'local');
            if (! $newVideoPath) {
                throw new RuntimeException('The lesson video could not be saved.');
            }
            $validated['video_path'] = $newVideoPath;
            $validated['youtube_video_id'] = null;
        } elseif ($youtubeId || $removeVideo) {
            $validated['video_path'] = null;
            $validated['youtube_video_id'] = $youtubeId;
        } elseif ($content) {
            $validated['video_path'] = $content->video_path;
            $validated['youtube_video_id'] = $content->youtube_video_id;
        }

        return [$validated, $newVideoPath];
    }

    private function formData(): array
    {
        return [
            'competencies' => Competency::orderBy('sequence')->get(),
            'languages' => ProgrammingLanguage::where('status', 'active')->orderBy('name')->get(),
        ];
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'content';
        $slug = $base;
        $suffix = 2;

        while (LearningContent::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function isInstructor(): bool
    {
        return request()->routeIs('instructor.*');
    }

    private function routeName(string $name): string
    {
        return ($this->isInstructor() ? 'instructor.' : 'admin.').$name;
    }

    private function authorizeOwner(LearningContent $content): void
    {
        if ($this->isInstructor()) {
            abort_unless($content->created_by === auth()->id(), 403);
        }
    }
}
