<div class="page-heading"><div><p class="eyebrow">Coding exercise</p><h1>{{ $exercise->title }}</h1><p class="lead">{{ $exercise->programmingLanguage?->name ?? 'Use your preferred language' }}</p></div></div>
@if ($requiresTimer)<div class="activity-timer" role="timer">Time remaining: <strong data-time-remaining>--:--</strong></div>@endif
@if ($retrySubmission)
    <div class="notice error" style="margin-bottom:18px">Your latest answer needs another attempt. The editor contains that code so you can correct it and submit again. <a href="{{ route('student.submissions.show', $retrySubmission) }}">Review the errors</a>.</div>
@endif
<section class="panel" style="margin-bottom:18px"><div class="panel-header"><h2>Task</h2></div><div style="white-space:pre-wrap;line-height:1.7">{{ $exercise->prompt }}</div></section>
<section class="panel" style="margin-bottom:18px">
    <div class="panel-header"><h2>Your solution</h2><span class="panel-kicker">Code editor</span></div>
    <form method="POST" action="{{ route('student.exercises.submit', $exercise) }}" data-code-form data-student-activity-locked>
        @csrf
        <p class="form-help" style="margin:0 0 14px">Copy, paste, and Find keyboard shortcuts are disabled while you solve this exercise.</p>
        @if ($exercise->programmingLanguage)
            <p class="form-help" style="margin:0 0 14px">Language: <strong>{{ $exercise->programmingLanguage->name }}</strong></p>
        @else
            <div class="form-group" style="margin-bottom:14px">
                <label class="form-label" for="source_language">Programming language</label>
                <input class="field" id="source_language" name="source_language" list="source-language-options" value="{{ old('source_language', $retrySubmission?->source_language) }}" placeholder="Choose or type any language" autocomplete="off" maxlength="100" data-code-language>
                <datalist id="source-language-options">
                    @foreach ($languages as $language)
                        <option value="{{ $language->name }}"></option>
                    @endforeach
                </datalist>
                <span class="form-help">Choose a language for highlighting, or type another language to write in plain text.</span>
            </div>
        @endif
        <div class="form-group">
            <label class="form-label" for="source_code">Code</label>
            <div class="student-code-editor" data-code-editor data-language="{{ $exercise->programmingLanguage?->slug ?? '' }}" aria-label="Code editor"></div>
            <textarea class="textarea student-code-fallback" id="source_code" name="source_code" spellcheck="false" required>{{ old('source_code', $retrySubmission?->source_code ?? $exercise->starter_code) }}</textarea>
            <span class="form-help">Use the editor to write your solution, then submit it for review. Code is not run automatically.</span>
            <span class="validation" data-code-error role="alert" hidden>Please enter code before submitting.</span>
        </div>
        <div class="form-actions"><a class="button secondary" href="{{ route('student.exercises.index') }}">All exercises</a><button class="button">Submit code</button></div>
    </form>
</section>
<section class="panel">
    <div class="panel-header"><h2>Previous submissions</h2></div>
    <div class="list">
        @forelse ($submissions as $submission)
            <article class="code-history-item {{ $loop->first ? '' : 'is-previous' }}">
                <div class="code-history-heading">
                    <div>
                        <div class="list-title">{{ $submission->submitted_at?->format('M j, Y g:i A') }}</div>
                        <div class="list-subtitle">{{ str_replace('_', ' ', ucfirst($submission->status)) }}</div>
                    </div>
                    <span class="badge {{ $loop->first ? 'success' : '' }}">{{ $loop->first ? 'Latest attempt' : 'Previous attempt · inactive' }}</span>
                </div>
                <pre class="code-history-code" aria-label="Submitted code">{{ $submission->source_code }}</pre>
            </article>
        @empty
            <div class="empty">No submissions yet.</div>
        @endforelse
    </div>
</section>
