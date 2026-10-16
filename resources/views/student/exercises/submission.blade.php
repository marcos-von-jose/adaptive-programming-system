@extends('layouts.dashboard')
@section('title', 'Code Submission')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Code submission</p><h1>{{ $submission->exercise?->title ?? 'Coding exercise' }}</h1><p class="lead">Submitted {{ $submission->submitted_at?->format('M j, Y g:i A') }} · {{ str_replace('_', ' ', ucfirst($submission->status)) }}</p></div></div>
@include('admin.partials.alerts')
@if ($isPreviousAttempt)
    <div class="notice" style="margin-bottom:18px">This is a previous attempt. A newer code submission has replaced it. You can still read this code and its feedback.</div>
@endif
@if ($submission->status === 'needs_retry')
    @php($problemLines = $submission->feedback->flatMap(fn ($entry) => $entry->review_outcome === 'incorrect' ? $entry->markedErrorLines() : [])->unique()->sort()->values()->all())
    <div class="notice error" style="margin-bottom:18px">{{ $isPreviousAttempt ? 'This previous answer was marked wrong.' : 'Your answer needs another attempt.' }}@if ($problemLines) Problem {{ count($problemLines) === 1 ? 'line' : 'lines' }}: {{ implode(', ', $problemLines) }}.@endif Review the feedback below.@if (! $isPreviousAttempt) Then submit corrected code.@endif</div>
@elseif ($submission->status === 'correct')
    <div class="notice" style="margin-bottom:18px">Your answer was marked correct.</div>
@endif
@if (! $isPreviousAttempt && $submission->exercise?->status === 'published' && $submission->status !== 'correct')
    <p style="margin:0 0 18px"><a class="button secondary" href="{{ route('student.exercises.show', $submission->exercise) }}">{{ $submission->status === 'needs_retry' ? 'Retake' : 'Practice again' }}</a></p>
@endif
<section class="panel" style="margin-bottom:18px">
    <div class="panel-header"><h2>Submitted solution</h2><span class="panel-kicker">{{ $submission->source_language ?? $submission->exercise?->programmingLanguage?->name ?? 'Language not specified' }}</span></div>
    @include('partials.submitted-code', ['submission' => $submission])
</section>
<section class="panel"><div class="panel-header"><h2>Review feedback</h2></div><div class="list">
    @forelse ($submission->feedback as $entry)
        <div class="list-item"><div class="list-copy">
            <div class="list-title">{{ $entry->author?->name ?? 'Reviewer' }} · {{ $entry->created_at->format('M j, Y') }}@if ($entry->review_outcome) · {{ $entry->review_outcome === 'incorrect' ? 'Wrong answer' : 'Correct answer' }}@endif</div>
            @if ($entry->error_message)<p style="margin:8px 0;font-size:13px;line-height:1.6">@if ($entry->markedErrorLines())<strong>Problem {{ count($entry->markedErrorLines()) === 1 ? 'line' : 'lines' }}: @foreach ($entry->markedErrorLines() as $line)<a href="#code-line-{{ $submission->id }}-{{ $line }}">{{ $line }}</a>{{ $loop->last ? '' : ', ' }}@endforeach. </strong>@endif{{ $entry->error_message }}</p>@endif
            <div class="list-subtitle">{{ $entry->message }}</div>
        </div></div>
    @empty
        <div class="empty">No review feedback yet.</div>
    @endforelse
</div></section>
@endsection
