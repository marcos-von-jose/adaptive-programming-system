@extends('layouts.dashboard')
@section('title', 'Instructor Dashboard')
@section('content')
@php
    $weeklyAttempts = array_sum(array_column($attemptTrend, 'value'));
    $attemptMax = max(1, ...array_column($attemptTrend, 'value'));
    $resourceTotal = $contentCount + $exerciseCount + $assessmentCount;
    $contentShare = $resourceTotal ? $contentCount / $resourceTotal * 100 : 0;
    $exerciseShare = $resourceTotal ? $exerciseCount / $resourceTotal * 100 : 0;
@endphp
<div class="page-heading">
    <div><p class="eyebrow">Teaching workspace</p><h1>Welcome back, {{ auth()->user()->name }}</h1><p class="lead">See how your materials are being used and keep student learning on track.</p></div>
    <span class="status-pill">Instructor workspace</span>
</div>

<section class="overview-hero-grid" aria-label="Teaching highlights">
    <article class="overview-feature">
        <span class="feature-kicker">Your teaching hub</span>
        <h2>Make every lesson count.</h2>
        <p>{{ number_format($publishedContentCount) }} of your {{ number_format($contentCount) }} learning materials are published for students.</p>
        <a class="feature-link" href="{{ route('instructor.content.create') }}">Create learning content →</a>
    </article>
    <article class="overview-metric">
        <div class="metric-top"><span>Your assessments</span><span class="metric-icon">✓</span></div>
        <div class="metric-value">{{ number_format($assessmentCount) }}</div>
        <div class="metric-note">{{ number_format($attemptCount) }} student attempts recorded</div>
        <a class="metric-link" href="{{ route('instructor.assessments.index') }}">Manage assessments →</a>
    </article>
    <article class="overview-metric">
        <div class="metric-top"><span>Registered students</span><span class="metric-icon">↗</span></div>
        <div class="metric-value">{{ number_format($studentCount) }}</div>
        <div class="metric-note">Learners across the platform</div>
        <a class="metric-link" href="{{ route('instructor.progress.index') }}">Monitor student progress →</a>
    </article>
</section>

<div class="overview-grid">
    <section class="panel" aria-label="Attempts on your assessments">
        <div class="chart-heading"><div><h2>Assessment activity</h2><p>Attempts on your assessments over the last 7 days</p></div><span class="chart-total">{{ number_format($weeklyAttempts) }} attempts</span></div>
        @if ($weeklyAttempts)
            <div class="bar-chart" role="img" aria-label="Assessment attempts by day: {{ collect($attemptTrend)->map(fn ($day) => $day['date'].': '.$day['value'])->join(', ') }}">
                @foreach ($attemptTrend as $day)
                    <div class="bar-column"><span class="bar-value">{{ $day['value'] }}</span><span class="bar-track"><span class="bar-fill" style="--height: {{ round($day['value'] / $attemptMax * 100) }}%"></span></span><span class="bar-label">{{ $day['label'] }}</span></div>
                @endforeach
            </div>
        @else
            <div class="empty">No assessment attempts in the last 7 days.</div>
        @endif
    </section>
    <section class="panel" aria-label="Your teaching resources">
        <div class="chart-heading"><div><h2>Resource overview</h2><p>Materials you created</p></div></div>
        <div class="donut-wrap"><div class="donut" style="--donut: {{ $resourceTotal ? 'conic-gradient(var(--chart-primary) 0% '.$contentShare.'%, var(--chart-secondary) '.$contentShare.'% '.($contentShare + $exerciseShare).'%, var(--chart-neutral) '.($contentShare + $exerciseShare).'% 100%)' : 'var(--chart-neutral)' }}"><div class="donut-inner"><strong>{{ number_format($resourceTotal) }}</strong><span>Total resources</span></div></div></div>
        <div class="legend"><span class="legend-item"><i class="legend-dot" style="--dot:var(--chart-primary)"></i>Lessons {{ $contentCount }}</span><span class="legend-item"><i class="legend-dot" style="--dot:var(--chart-secondary)"></i>Exercises {{ $exerciseCount }}</span><span class="legend-item"><i class="legend-dot" style="--dot:var(--chart-neutral)"></i>Assessments {{ $assessmentCount }}</span></div>
    </section>
</div>

<div class="overview-bottom">
    <section class="panel"><div class="panel-header"><h2>Recent code submissions</h2><a class="button secondary small" href="{{ route('instructor.progress.index') }}">View progress</a></div><div class="list">
        @forelse ($recentSubmissions as $submission)
            <div class="list-item"><span class="list-icon">{{ strtoupper(substr($submission->student?->name ?? 'S', 0, 2)) }}</span><div class="list-copy"><div class="list-title">{{ $submission->student?->name ?? 'Student' }} · {{ $submission->exercise?->title ?? 'Exercise' }}</div><div class="list-subtitle">Submitted {{ $submission->submitted_at?->format('M j, g:i A') ?? $submission->created_at->format('M j, g:i A') }}</div></div><span class="badge">{{ $submission->status }}</span></div>
        @empty
            <div class="empty">No code submissions on your exercises yet.</div>
        @endforelse
    </div></section>
    <section class="panel"><div class="panel-header"><h2>Teaching tools</h2></div><div class="quick-grid">
        <a class="quick-action" href="{{ route('instructor.content.create') }}"><div class="quick-title">Manage Content</div><div class="quick-copy">Create a new lesson.</div></a>
        <a class="quick-action" href="{{ route('instructor.assessments.create') }}"><div class="quick-title">Manage Assessments</div><div class="quick-copy">Build an evaluation.</div></a>
        <a class="quick-action" href="{{ route('instructor.feedback.create') }}"><div class="quick-title">Provide Feedback</div><div class="quick-copy">Guide a learner.</div></a>
        <a class="quick-action" href="{{ route('instructor.analytics.index') }}"><div class="quick-title">View Analytics</div><div class="quick-copy">Explore learning results.</div></a>
        <a class="quick-action" href="{{ route('instructor.progress.index') }}"><div class="quick-title">Monitor Student Progress</div><div class="quick-copy">Review recent results.</div></a>
        <a class="quick-action" href="{{ route('instructor.profile.edit') }}"><div class="quick-title">Profile Info</div><div class="quick-copy">Update your details.</div></a>
    </div></section>
</div>
@endsection
