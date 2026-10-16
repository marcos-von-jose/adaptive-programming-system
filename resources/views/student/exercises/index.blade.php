@extends('layouts.dashboard')
@section('title', 'Coding Exercises')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Programming practice</p><h1>Solve and submit code</h1><p class="lead">Choose an exercise, write a solution, and submit it for review.</p></div></div>
@if (! $lessonStatus['ready'])
    <div class="notice error">
        @if ($lessonStatus['total'] === 0)
            Coding exercises are locked until an instructor or administrator publishes a lesson and you complete it.
        @else
            Complete all published learning materials before solving a coding exercise. {{ $lessonStatus['remaining'] }} {{ $lessonStatus['remaining'] === 1 ? 'lesson remains' : 'lessons remain' }}.
        @endif
        <a href="{{ route('student.learning.index') }}">Go to Learning Materials</a>
    </div>
@endif
<section class="panel"><div class="list">
    @forelse ($exercises as $exercise)
        <div class="list-item">
            <span class="list-icon">&lt;/&gt;</span>
            <div class="list-copy"><div class="list-title">{{ $exercise->title }}</div><div class="list-subtitle">{{ $exercise->programmingLanguage?->name ?? 'Any language' }} &middot; {{ $exercise->time_limit_minutes ? $exercise->time_limit_minutes.' min limit' : 'No time limit' }} &middot; {{ $exercise->my_submissions_count }} submissions</div></div>
            @if ($exercise->my_latest_submission_id)
                @if ($exercise->my_latest_submission_status !== 'correct' && $lessonStatus['ready'])
                    <a class="button secondary small" href="{{ route('student.exercises.show', $exercise) }}">{{ $exercise->my_latest_submission_status === 'needs_retry' ? 'Retake' : 'Practice again' }}</a>
                @elseif ($exercise->my_latest_submission_status !== 'correct')
                    <span class="badge warning">Locked</span>
                @endif
                <a class="button small" href="{{ route('student.submissions.show', $exercise->my_latest_submission_id) }}">View Submitted Code</a>
            @elseif ($lessonStatus['ready'])
                <a class="button small" href="{{ route('student.exercises.show', $exercise) }}">Solve</a>
            @else
                <span class="badge warning">Locked</span>
            @endif
        </div>
    @empty
        <div class="empty">No coding exercises are published yet.</div>
    @endforelse
</div></section>
@endsection
