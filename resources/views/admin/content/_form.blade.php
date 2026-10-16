@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@php($editing = isset($content))
<div class="form-grid">
    <div class="form-group full"><label class="form-label" for="title">Title</label><input class="field" id="title" name="title" value="{{ old('title', $content->title ?? '') }}" required></div>
    <div class="form-group full"><label class="form-label" for="summary">Summary</label><textarea class="textarea" id="summary" name="summary" style="min-height:80px">{{ old('summary', $content->summary ?? '') }}</textarea><span class="form-help">A short description shown in content listings.</span></div>
    <div class="form-group full"><label class="form-label" for="body">Lesson content</label><textarea class="textarea" id="body" name="body" style="min-height:280px" required>{{ old('body', $content->body ?? '') }}</textarea></div>
    <div class="form-group full">
        <label class="form-label" for="video_upload">Upload a lesson video (optional)</label>
        <input class="field" type="file" id="video_upload" name="video_upload" accept="video/mp4,video/webm,video/ogg,.ogv">
        <span class="form-help">MP4, WebM, or Ogg, up to 100 MB if the server allows it. Use either an upload or a YouTube link.</span>
    </div>
    <div class="form-group full">
        <label class="form-label" for="youtube_url">YouTube video link (optional)</label>
        <input class="field" type="url" id="youtube_url" name="youtube_url" value="{{ old('youtube_url') }}" placeholder="https://www.youtube.com/watch?v=...">
        <span class="form-help">Paste a YouTube watch, Shorts, live, or youtu.be link.</span>
    </div>
    @if ($editing && ($content->video_path || $content->youtube_video_id))
        <div class="form-group full">
            <span class="form-label">Current video</span>
            @if ($content->video_path)
                <a href="{{ route('lessons.video', $content) }}" target="_blank" rel="noopener">View uploaded video</a>
            @else
                <a href="https://www.youtube.com/watch?v={{ $content->youtube_video_id }}" target="_blank" rel="noopener noreferrer">View YouTube video</a>
            @endif
            <label class="form-help" for="remove_video"><input type="checkbox" id="remove_video" name="remove_video" value="1" @checked(old('remove_video'))> Remove the current video</label>
            <span class="form-help">Uploading a new video or adding a link replaces the current video.</span>
        </div>
    @endif
    <div class="form-group"><label class="form-label" for="competency_id">Competency</label><select class="select" id="competency_id" name="competency_id"><option value="">General</option>@foreach ($competencies as $competency)<option value="{{ $competency->id }}" @selected(old('competency_id', $content->competency_id ?? '') == $competency->id)>{{ $competency->code }} — {{ $competency->name }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label" for="programming_language_id">Programming language</label><select class="select" id="programming_language_id" name="programming_language_id"><option value="">Language independent</option>@foreach ($languages as $language)<option value="{{ $language->id }}" @selected(old('programming_language_id', $content->programming_language_id ?? '') == $language->id)>{{ $language->name }} {{ $language->version }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label" for="status">Status</label><select class="select" id="status" name="status" required><option value="draft" @selected(old('status', $content->status ?? 'draft') === 'draft')>Draft</option><option value="published" @selected(old('status', $content->status ?? '') === 'published')>Published</option></select></div>
</div>
<div class="form-actions"><a class="button secondary" href="{{ route($managementPrefix.'.content.index') }}">Cancel</a><button class="button" type="submit">{{ $editing ? 'Save content' : 'Create content' }}</button></div>
