@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@extends('layouts.dashboard')
@section('title', 'New Feedback')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Student guidance</p><h1>New feedback</h1><p class="lead">Read the submission, identify errors, and tell the student what to improve.</p></div></div>
@include('admin.partials.alerts')

@if ($selectedSubmission)
    <section class="panel" style="margin-bottom:18px">
        <div class="panel-header"><h2>{{ $selectedSubmission->exercise?->title ?? 'Code submission' }}</h2><span class="panel-kicker">{{ $selectedSubmission->source_language ?? 'Language not specified' }}</span></div>
        <p class="form-help" style="margin:0 0 10px">Check every line that has a problem. <span data-selected-line-count aria-live="polite"></span></p>
        @include('partials.submitted-code', ['submission' => $selectedSubmission, 'linePicker' => true, 'selectedLines' => array_map('intval', old('error_lines', []))])
        @if ($selectedSubmission->feedback->isNotEmpty())
            <div class="list" style="margin-top:18px">
                @foreach ($selectedSubmission->feedback as $entry)
                    <div class="list-item"><div class="list-copy"><div class="list-title">{{ $entry->review_outcome === 'incorrect' ? 'Wrong answer' : ($entry->review_outcome === 'correct' ? 'Correct' : 'Feedback') }}@if ($entry->markedErrorLines()) · Lines {{ implode(', ', $entry->markedErrorLines()) }}@endif</div><div class="list-subtitle">{{ $entry->error_message ?: $entry->message }}</div></div></div>
                @endforeach
            </div>
        @endif
    </section>
@endif

<section class="panel">
    <form id="review-feedback-form" method="POST" action="{{ route($managementPrefix.'.feedback.store') }}">@csrf
        <div class="form-grid">
            <div class="form-group"><label class="form-label" for="student_id">Student</label><select class="select" id="student_id" name="student_id" required data-review-student-select><option value="">Choose student</option>@foreach ($students as $student)<option value="{{ $student->id }}" @selected(old('student_id', $studentId) == $student->id)>{{ $student->name }} ({{ $student->email }})</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label" for="assessment_attempt_id">Assessment attempt (optional)</label><select class="select" id="assessment_attempt_id" name="assessment_attempt_id"><option value="">General feedback</option>@foreach ($attempts as $attempt)<option value="{{ $attempt->id }}" @selected(old('assessment_attempt_id') == $attempt->id)>{{ $attempt->assessment?->title }} · {{ $attempt->score }}%</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label" for="code_submission_id">Code submission (optional)</label><select class="select" id="code_submission_id" name="code_submission_id" data-review-submission-select><option value="">No code submission</option>@foreach ($submissions as $submission)<option value="{{ $submission->id }}" @selected(old('code_submission_id', request('code_submission_id')) == $submission->id)>{{ $submission->exercise?->title ?? 'Exercise' }} · {{ $submission->submitted_at?->format('M j, Y') }}</option>@endforeach</select><span class="form-help">Selecting a submission opens its code above this form.</span></div>
            @if ($selectedSubmission)
                <div class="form-group"><label class="form-label" for="review_outcome">Review result</label><select class="select" id="review_outcome" name="review_outcome" required><option value="">Choose result</option><option value="correct" @selected(old('review_outcome') === 'correct')>Correct answer</option><option value="incorrect" @selected(old('review_outcome') === 'incorrect')>Wrong answer — student must retry</option></select></div>
                <input type="hidden" name="error_lines_present" value="1">
                <div class="form-group full"><label class="form-label" for="error_message">What is wrong?</label><textarea class="textarea" id="error_message" name="error_message" placeholder="Describe the error or why the answer is wrong.">{{ old('error_message') }}</textarea></div>
            @endif
            <div class="form-group full"><label class="form-label" for="message">Feedback for the student</label><textarea class="textarea" id="message" name="message" required>{{ old('message') }}</textarea></div>
        </div>
        <div class="form-actions"><a class="button secondary" href="{{ route($managementPrefix.'.feedback.index') }}">Cancel</a><button class="button" type="submit">Send feedback</button></div>
    </form>
</section>
@endsection
