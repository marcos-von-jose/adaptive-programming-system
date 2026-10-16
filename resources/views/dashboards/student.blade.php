@extends('layouts.dashboard')
@section('title', 'Student Dashboard')
@section('content')
@php
    $completionShare = $publishedContentCount ? min(100, $completedLessons / $publishedContentCount * 100) : 0;
@endphp
<div class="page-heading">
    <div><p class="eyebrow">Learning workspace</p><h1>Welcome back, {{ auth()->user()->name }}</h1><p class="lead">Keep learning, track your progress, and see what to work on next.</p></div>
    <span class="status-pill">Student workspace</span>
</div>

<section class="overview-hero-grid" aria-label="Learning highlights">
    <article class="overview-feature">
        <span class="feature-kicker">Your learning journey</span>
        <h2>One step closer to your next milestone.</h2>
        <p>{{ number_format($completedLessons) }} of {{ number_format($publishedContentCount) }} published lessons completed.</p>
        <a class="feature-link" href="{{ route('student.learning.index') }}">Access learning materials →</a>
    </article>
    <article class="overview-metric">
        <div class="metric-top"><span>Average assessment score</span><span class="metric-icon">✓</span></div>
        <div class="metric-value">{{ $averageScore === null ? '—' : round($averageScore, 1).'%' }}</div>
        <div class="metric-note">Across {{ number_format($attemptCount) }} assessment attempts</div>
        <a class="metric-link" href="{{ route('student.assessments.index') }}">Take an assessment →</a>
    </article>
    <article class="overview-metric">
        <div class="metric-top"><span>Code submissions</span><span class="metric-icon">⌘</span></div>
        <div class="metric-value">{{ number_format($submissionCount) }}</div>
        <div class="metric-note">Solutions submitted for coding exercises</div>
        <a class="metric-link" href="{{ route('student.exercises.index') }}">Solve and submit code →</a>
    </article>
</section>

<div class="overview-grid">
    <section class="panel" aria-label="Recent assessment scores">
        <div class="chart-heading"><div><h2>Assessment score history</h2><p>Your most recent assessment attempts</p></div><a class="button secondary small" href="{{ route('student.progress.index') }}">View progress</a></div>
        @if ($recentAttempts->isNotEmpty())
            <div class="bar-chart" role="img" aria-label="Recent scores: {{ $recentAttempts->map(fn ($attempt) => ($attempt->assessment?->title ?? 'Assessment').': '.round($attempt->score, 1).'%')->join(', ') }}">
                @foreach ($recentAttempts as $attempt)
                    <div class="bar-column" title="{{ $attempt->assessment?->title ?? 'Assessment' }} · {{ round($attempt->score, 1) }}%"><span class="bar-value">{{ round($attempt->score) }}%</span><span class="bar-track"><span class="bar-fill" style="--height: {{ min(100, max(0, round($attempt->score))) }}%"></span></span><span class="bar-label">{{ $attempt->completed_at?->format('M j') ?? 'Recent' }}</span></div>
                @endforeach
            </div>
        @else
            <div class="empty">Take an assessment to see your score history.</div>
        @endif
    </section>
    <section class="panel" aria-label="Lesson completion">
        <div class="chart-heading"><div><h2>Learning progress</h2><p>Published lessons completed</p></div></div>
        <div class="donut-wrap"><div class="donut" style="--donut: {{ $publishedContentCount ? 'conic-gradient(var(--chart-primary) 0% '.$completionShare.'%, var(--chart-neutral) '.$completionShare.'% 100%)' : 'var(--chart-neutral)' }}"><div class="donut-inner"><strong>{{ round($completionShare) }}%</strong><span>Completed</span></div></div></div>
        <div class="legend"><span class="legend-item"><i class="legend-dot" style="--dot:var(--chart-primary)"></i>{{ $completedLessons }} completed</span><span class="legend-item"><i class="legend-dot" style="--dot:var(--chart-neutral)"></i>{{ max(0, $publishedContentCount - $completedLessons) }} remaining</span></div>
    </section>
</div>

<div class="overview-bottom">
    <section class="panel"><div class="panel-header"><h2>Recent feedback</h2><a class="button secondary small" href="{{ route('student.feedback.index') }}">Receive Feedback</a></div><div class="list">
        @forelse ($recentFeedback as $entry)
            <div class="list-item"><span class="list-icon">{{ strtoupper(substr($entry->author?->name ?? 'R', 0, 2)) }}</span><div class="list-copy"><div class="list-title">{{ $entry->author?->name ?? 'Reviewer' }} · {{ $entry->created_at->format('M j, Y') }}</div>@if ($entry->markedErrorLines())<div class="list-subtitle">Problem {{ count($entry->markedErrorLines()) === 1 ? 'line' : 'lines' }} {{ implode(', ', $entry->markedErrorLines()) }} · <a href="{{ route('student.submissions.show', $entry->code_submission_id) }}">View marked code</a></div>@endif<div class="list-subtitle">{{ $entry->message }}</div></div></div>
        @empty
            <div class="empty">No feedback yet. Your instructor's comments will appear here.</div>
        @endforelse
    </div></section>
    <section class="panel"><div class="panel-header"><h2>Your next steps</h2></div><div class="quick-grid">
        <a class="quick-action" href="{{ route('student.assessments.index') }}"><div class="quick-title">Take Assessment</div><div class="quick-copy">Check what you know.</div></a>
        <a class="quick-action" href="{{ route('student.learning.index') }}"><div class="quick-title">Access Learning Materials</div><div class="quick-copy">Continue your lessons.</div></a>
        <a class="quick-action" href="{{ route('student.exercises.index') }}"><div class="quick-title">Solve and Submit Code</div><div class="quick-copy">Practice a new challenge.</div></a>
        <a class="quick-action" href="{{ route('student.progress.index') }}"><div class="quick-title">View Progress</div><div class="quick-copy">Review your activity.</div></a>
        <a class="quick-action" href="{{ route('student.feedback.index') }}"><div class="quick-title">Receive Feedback</div><div class="quick-copy">Read your guidance.</div></a>
        <a class="quick-action" href="{{ route('student.profile.edit') }}"><div class="quick-title">Profile Info</div><div class="quick-copy">Update your details.</div></a>
    </div></section>
</div>
@endsection
