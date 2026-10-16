@extends('layouts.dashboard')
@section('title', 'Assessments')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Check your skills</p><h1>Assessments</h1><p class="lead">Choose an assessment and see your results after submitting.</p></div></div>
@include('admin.partials.alerts')
@if (! $lessonStatus['ready'])
    <div class="notice error">
        @if ($lessonStatus['total'] === 0)
            Assessments are locked until an instructor or administrator publishes a lesson and you complete it.
        @else
            Complete all published learning materials before taking an assessment. {{ $lessonStatus['remaining'] }} {{ $lessonStatus['remaining'] === 1 ? 'lesson remains' : 'lessons remain' }}.
        @endif
        <a href="{{ route('student.learning.index') }}">Go to Learning Materials</a>
    </div>
@endif
<section class="panel"><div class="list">
    @forelse ($assessments as $assessment)
        <div class="list-item">
            <span class="list-icon">{{ $assessment->items_count }}</span>
            <div class="list-copy"><div class="list-title">{{ $assessment->title }}</div>
                <div class="list-subtitle">{{ $assessment->competency?->name ?? 'General' }} &middot; {{ $assessment->items_count }} questions &middot; {{ $assessment->time_limit_minutes ? $assessment->time_limit_minutes.' min limit' : 'No time limit' }} &middot; Pass at {{ $assessment->passing_score }}% &middot; {{ $assessment->my_attempts_count }} attempts @if ($assessment->best_score !== null) &middot; Best {{ $assessment->best_score }}% @endif</div>
            </div>
            @if ($assessment->my_attempts_count > 0)
                <a class="button small" href="{{ route('student.assessments.show', $assessment) }}">View Assessment</a>
            @elseif ($lessonStatus['ready'])
                <a class="button small" href="{{ route('student.assessments.show', $assessment) }}">Start</a>
            @else
                <span class="badge warning">Locked</span>
            @endif
        </div>
    @empty
        <div class="empty">No assessments are available yet.</div>
    @endforelse
</div></section>
@endsection
