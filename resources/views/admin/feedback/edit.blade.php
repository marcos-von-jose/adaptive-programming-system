@extends('layouts.dashboard')
@section('title', 'Edit Feedback')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Student guidance</p><h1>Edit feedback</h1><p class="lead">Update the message sent to {{ $feedback->student?->name ?? 'this student' }}.</p></div></div>
@include('admin.partials.alerts')
@if ($feedback->codeSubmission)
    <section class="panel" style="margin-bottom:18px">
        <div class="panel-header"><h2>Submitted code</h2><span class="panel-kicker">{{ $feedback->codeSubmission->source_language ?? 'Language not specified' }}</span></div>
        <p class="form-help" style="margin:0 0 10px">Check every line that has a problem. <span data-selected-line-count aria-live="polite"></span></p>
        @include('partials.submitted-code', ['submission' => $feedback->codeSubmission, 'linePicker' => true, 'selectedLines' => array_map('intval', old('error_lines', $feedback->markedErrorLines()))])
    </section>
@endif
<section class="panel">
    <form id="review-feedback-form" method="POST" action="{{ route('admin.feedback.update', $feedback) }}">@csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group"><span class="form-label">Student</span><div class="field">{{ $feedback->student?->name ?? 'Removed student' }}</div></div>
            <div class="form-group"><span class="form-label">Related work</span><div class="field">{{ $feedback->codeSubmission?->exercise?->title ?? $feedback->attempt?->assessment?->title ?? 'General feedback' }}</div></div>
            @if ($feedback->codeSubmission)
                <div class="form-group"><label class="form-label" for="review_outcome">Review result</label><select class="select" id="review_outcome" name="review_outcome"><option value="">Feedback only</option><option value="correct" @selected(old('review_outcome', $feedback->review_outcome) === 'correct')>Correct answer</option><option value="incorrect" @selected(old('review_outcome', $feedback->review_outcome) === 'incorrect')>Wrong answer — student must retry</option></select></div>
                <input type="hidden" name="error_lines_present" value="1">
                <div class="form-group full"><label class="form-label" for="error_message">What is wrong?</label><textarea class="textarea" id="error_message" name="error_message">{{ old('error_message', $feedback->error_message) }}</textarea></div>
            @endif
            <div class="form-group full"><label class="form-label" for="message">Message</label><textarea class="textarea" id="message" name="message" required>{{ old('message', $feedback->message) }}</textarea></div>
        </div>
        <div class="form-actions"><a class="button secondary" href="{{ route('admin.feedback.index') }}">Cancel</a><button class="button" type="submit">Save changes</button></div>
    </form>
</section>
@endsection
