@extends('layouts.dashboard')
@section('title', 'Reports')
@section('content')
@include('partials.analytics-styles')
@php($registrationMax = max(1, $registrationChart->max('total')))
<div class="analytics-page">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Analytics</p>
            <h1>System reports</h1>
            <p class="lead">Review user growth, learning activity, and assessment performance.</p>
            <p class="analytics-print-date">Generated {{ now()->format('M j, Y g:i A') }}</p>
        </div>
        <button class="button analytics-print-button" type="button" onclick="window.print()">Print report</button>
    </div>

    <section class="analytics-kpis" aria-label="System highlights">
        <article class="analytics-card">
            <h2 class="analytics-card-label">Total users</h2>
            <p class="analytics-card-value">{{ number_format($totalUsers) }}</p>
            <p class="analytics-card-note">Current system accounts</p>
            <div class="analytics-card-footer"><span>Joined this month</span><strong>+{{ number_format($newUsersThisMonth) }}</strong></div>
        </article>
        <article class="analytics-card">
            <h2 class="analytics-card-label">Published lessons</h2>
            <p class="analytics-card-value">{{ number_format($publishedContent) }}</p>
            <p class="analytics-card-note">{{ $publicationRate }}% of lesson content published</p>
            <div class="analytics-track" role="progressbar" aria-label="Published lesson share" aria-valuenow="{{ $publicationRate }}" aria-valuemin="0" aria-valuemax="100"><span class="analytics-fill" style="width: {{ $publicationRate }}%; background: var(--analytics-amber)"></span></div>
            <div class="analytics-card-footer"><span>Draft lessons</span><strong>{{ number_format($draftContent) }}</strong></div>
        </article>
        <article class="analytics-card">
            <h2 class="analytics-card-label">Assessment pass rate</h2>
            <p class="analytics-card-value">{{ $passRate }}%</p>
            <p class="analytics-card-note">{{ number_format($passedAttempts) }} passed of {{ number_format($assessmentAttempts) }} attempts</p>
            <div class="analytics-track" role="progressbar" aria-label="Assessment pass rate" aria-valuenow="{{ $passRate }}" aria-valuemin="0" aria-valuemax="100"><span class="analytics-fill" style="width: {{ $passRate }}%; background: var(--analytics-green)"></span></div>
            <div class="analytics-card-footer"><span>Average score</span><strong>{{ $averageScore === null ? 'No scores yet' : $averageScore.'%' }}</strong></div>
        </article>
        <article class="analytics-card">
            <h2 class="analytics-card-label">Code review coverage</h2>
            <p class="analytics-card-value">{{ $reviewRate }}%</p>
            <p class="analytics-card-note">{{ number_format($reviewedSubmissions) }} reviewed of {{ number_format($codeSubmissions) }} submissions</p>
            <div class="analytics-track" role="progressbar" aria-label="Code review coverage" aria-valuenow="{{ $reviewRate }}" aria-valuemin="0" aria-valuemax="100"><span class="analytics-fill" style="width: {{ $reviewRate }}%"></span></div>
            <div class="analytics-card-footer"><span>Published coding exercises</span><strong>{{ number_format($publishedExercises) }}</strong></div>
        </article>
        <article class="analytics-card">
            <h2 class="analytics-card-label">Learning completions</h2>
            <p class="analytics-card-value">{{ number_format($completedLessons) }}</p>
            <p class="analytics-card-note">Completed student lessons</p>
            <div class="analytics-card-footer"><span>Feedback messages</span><strong>{{ number_format($feedbackCount) }}</strong></div>
        </article>
        <article class="analytics-card">
            <h2 class="analytics-card-label">Published assessments</h2>
            <p class="analytics-card-value">{{ number_format($publishedAssessments) }}</p>
            <p class="analytics-card-note">Available to students</p>
            <div class="analytics-card-footer"><span>Total attempts</span><strong>{{ number_format($assessmentAttempts) }}</strong></div>
        </article>
    </section>

    <div class="analytics-grid">
        <section class="panel">
            <div class="panel-header"><div><h2>Users by role</h2><p>Share of all {{ number_format($totalUsers) }} accounts</p></div></div>
            <div class="analytics-bars">
                @forelse ($usersByRole as $role)
                    <div class="analytics-bar-row">
                        <span class="analytics-bar-label" title="{{ $role->name }}">{{ $role->name }}</span>
                        <div class="analytics-track" role="progressbar" aria-label="{{ $role->name }} users" aria-valuenow="{{ $role->users_count }}" aria-valuemin="0" aria-valuemax="{{ max(1, $totalUsers) }}"><span class="analytics-fill" style="width: {{ $totalUsers ? round($role->users_count / $totalUsers * 100) : 0 }}%"></span></div>
                        <span class="analytics-bar-value">{{ $role->users_count }}</span>
                    </div>
                @empty
                    <div class="empty">No roles to display.</div>
                @endforelse
            </div>
        </section>
        <section class="panel">
            <div class="panel-header"><div><h2>New registrations</h2><p>Daily signups over the last seven days</p></div></div>
            <div class="analytics-columns" role="img" aria-label="Registrations over the last seven days">
                @foreach ($registrationChart as $day)
                    <div class="analytics-column" title="{{ $day['date'] }}: {{ $day['total'] }} new users">
                        <span class="analytics-column-value">{{ $day['total'] }}</span>
                        <span class="analytics-column-track"><span class="analytics-column-fill" style="--bar-height: {{ round($day['total'] / $registrationMax * 100) }}%"></span></span>
                        <span class="analytics-column-label">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <p class="analytics-card-note">{{ number_format($registrations->sum('total')) }} registrations in the last 30 days</p>
        </section>
    </div>

    <section class="panel analytics-section">
        <div class="panel-header"><div><h2>Assessment performance</h2><p>Average score and participation by assessment</p></div></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Assessment</th><th>Status</th><th>Attempts</th><th>Average score</th></tr></thead>
                <tbody>
                    @forelse ($assessmentPerformance as $assessment)
                        <tr>
                            <td class="cell-title">{{ $assessment->title }}</td>
                            <td><span class="badge {{ $assessment->status === 'published' ? 'success' : 'warning' }}">{{ $assessment->status }}</span></td>
                            <td>{{ $assessment->attempts_count }}</td>
                            <td class="analytics-score">
                                {{ $assessment->average_score === null ? 'No attempts' : round($assessment->average_score, 1).'%' }}
                                <div class="analytics-track" aria-hidden="true"><span class="analytics-fill" style="width: {{ max(0, min(100, $assessment->average_score ?? 0)) }}%"></span></div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty">No assessments yet.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
