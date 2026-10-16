@extends('layouts.dashboard')
@section('title', 'Administrator Dashboard')
@section('content')
@php
    $registrationTotal = array_sum(array_column($registrationTrend, 'value'));
    $registrationMax = max(1, ...array_column($registrationTrend, 'value'));
    $studentShare = $totalUsers ? ($studentCount / $totalUsers) * 100 : 0;
    $instructorShare = $totalUsers ? ($instructorCount / $totalUsers) * 100 : 0;
    $otherCount = max(0, $totalUsers - $studentCount - $instructorCount);
@endphp
<div class="page-heading">
    <div><p class="eyebrow">System overview</p><h1>Welcome back, {{ auth()->user()->name }}</h1><p class="lead">A clear view of your learners, teaching resources, and recent system activity.</p></div>
    <span class="status-pill">Administrator workspace</span>
</div>

<section class="overview-hero-grid" aria-label="Platform highlights">
    <article class="overview-feature">
        <span class="feature-kicker">Platform overview</span>
        <h2>Keep learning moving across the platform.</h2>
        <p>{{ number_format($publishedContentCount) }} published learning materials and {{ number_format($assessmentCount) }} assessments are available to manage.</p>
        <a class="feature-link" href="{{ route('admin.reports.index') }}">Explore analytics →</a>
    </article>
    <article class="overview-metric">
        <div class="metric-top"><span>Total users</span><span class="metric-icon">↗</span></div>
        <div class="metric-value">{{ number_format($totalUsers) }}</div>
        <div class="metric-note">{{ number_format($registrationTotal) }} registered in the last 7 days</div>
        @if ($registrationTotal)<div class="metric-spark" aria-hidden="true">@foreach ($registrationTrend as $day)<span style="--height: {{ round($day['value'] / $registrationMax * 100) }}%"></span>@endforeach</div>@endif
    </article>
    <article class="overview-metric">
        <div class="metric-top"><span>Assessment attempts</span><span class="metric-icon">✓</span></div>
        <div class="metric-value">{{ number_format($attemptCount) }}</div>
        <div class="metric-note">Completed by learners across the platform</div>
        <a class="metric-link" href="{{ route('admin.progress.index') }}">Review student progress →</a>
    </article>
</section>

<div class="overview-grid">
    <section class="panel" aria-label="New user registrations">
        <div class="chart-heading"><div><h2>New registrations</h2><p>Daily accounts created over the last 7 days</p></div><span class="chart-total">{{ number_format($registrationTotal) }} total</span></div>
        @if ($registrationTotal)
            <div class="bar-chart" role="img" aria-label="New registrations by day: {{ collect($registrationTrend)->map(fn ($day) => $day['date'].': '.$day['value'])->join(', ') }}">
                @foreach ($registrationTrend as $day)
                    <div class="bar-column"><span class="bar-value">{{ $day['value'] }}</span><span class="bar-track"><span class="bar-fill" style="--height: {{ round($day['value'] / $registrationMax * 100) }}%"></span></span><span class="bar-label">{{ $day['label'] }}</span></div>
                @endforeach
            </div>
        @else
            <div class="empty">No new registrations in the last 7 days.</div>
        @endif
    </section>
    <section class="panel" aria-label="User roles">
        <div class="chart-heading"><div><h2>Platform community</h2><p>Users by role</p></div></div>
        <div class="donut-wrap"><div class="donut" style="--donut: {{ $totalUsers ? 'conic-gradient(var(--chart-primary) 0% '.$studentShare.'%, var(--chart-secondary) '.$studentShare.'% '.($studentShare + $instructorShare).'%, var(--chart-neutral) '.($studentShare + $instructorShare).'% 100%)' : 'var(--chart-neutral)' }}"><div class="donut-inner"><strong>{{ number_format($totalUsers) }}</strong><span>Total users</span></div></div></div>
        <div class="legend"><span class="legend-item"><i class="legend-dot" style="--dot:var(--chart-primary)"></i>Students {{ $studentCount }}</span><span class="legend-item"><i class="legend-dot" style="--dot:var(--chart-secondary)"></i>Instructors {{ $instructorCount }}</span><span class="legend-item"><i class="legend-dot" style="--dot:var(--chart-neutral)"></i>Other {{ $otherCount }}</span></div>
    </section>
</div>

<div class="overview-bottom">
    <section class="panel"><div class="panel-header"><h2>Recent system activity</h2><a class="button secondary small" href="{{ route('admin.logs.index') }}">View logs</a></div><div class="list">
        @forelse ($recentLogs as $log)
            <div class="list-item"><span class="list-icon">{{ strtoupper(substr($log->action, 0, 2)) }}</span><div class="list-copy"><div class="list-title">{{ $log->description }}</div><div class="list-subtitle">{{ $log->user?->name ?? 'System' }} · {{ $log->created_at->format('M j, g:i A') }}</div></div></div>
        @empty
            <div class="empty">No recent system activity.</div>
        @endforelse
    </div></section>
    <section class="panel"><div class="panel-header"><h2>Quick actions</h2></div><div class="quick-grid">
        <a class="quick-action" href="{{ route('admin.users.index') }}"><div class="quick-title">Manage users</div><div class="quick-copy">Review accounts and roles.</div></a>
        <a class="quick-action" href="{{ route('admin.content.index') }}"><div class="quick-title">Manage content</div><div class="quick-copy">Maintain learning materials.</div></a>
        <a class="quick-action" href="{{ route('admin.assessments.index') }}"><div class="quick-title">Manage assessments</div><div class="quick-copy">Review published evaluations.</div></a>
        <a class="quick-action" href="{{ route('admin.progress.index') }}"><div class="quick-title">Monitor students progress</div><div class="quick-copy">Review learning results.</div></a>
        <a class="quick-action" href="{{ route('admin.feedback.create') }}"><div class="quick-title">Provide feedback</div><div class="quick-copy">Send guidance to a learner.</div></a>
        <a class="quick-action" href="{{ route('admin.reports.index') }}"><div class="quick-title">View analytics</div><div class="quick-copy">Explore platform reports.</div></a>
    </div></section>
</div>
@endsection
