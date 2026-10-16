@extends('layouts.dashboard')
@section('title', 'View Analytics')
@section('content')
@include('partials.analytics-styles')
@php($lessonMax = max(1, $contentPerformance->max('completions_count')))
@php($exerciseMax = max(1, $exercisePerformance->max('submissions_count')))
<div class="analytics-page">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Teaching outcomes</p>
            <h1>View analytics</h1>
            <p class="lead">See how students use your lessons and perform on your assessments.</p>
        </div>
    </div>

    <section class="analytics-kpis" aria-label="Teaching highlights">
        <article class="analytics-card">
            <h2 class="analytics-card-label">Published lessons</h2>
            <p class="analytics-card-value">{{ number_format($publishedContent) }}</p>
            <p class="analytics-card-note">Learning materials available to students</p>
            <div class="analytics-card-footer"><span>Student completions</span><strong>{{ number_format($completedLessons) }}</strong></div>
        </article>
        <article class="analytics-card">
            <h2 class="analytics-card-label">Published assessments</h2>
            <p class="analytics-card-value">{{ number_format($publishedAssessments) }}</p>
            <p class="analytics-card-note">Assessments available to students</p>
            <div class="analytics-card-footer"><span>Student attempts</span><strong>{{ number_format($attemptCount) }}</strong></div>
        </article>
        <article class="analytics-card">
            <h2 class="analytics-card-label">Assessment pass rate</h2>
            <p class="analytics-card-value">{{ $passRate }}%</p>
            <p class="analytics-card-note">{{ number_format($passedCount) }} passed of {{ number_format($attemptCount) }} attempts</p>
            <div class="analytics-track" role="progressbar" aria-label="Assessment pass rate" aria-valuenow="{{ $passRate }}" aria-valuemin="0" aria-valuemax="100"><span class="analytics-fill" style="width: {{ $passRate }}%; background: var(--analytics-green)"></span></div>
        </article>
        <article class="analytics-card">
            <h2 class="analytics-card-label">Average assessment score</h2>
            <p class="analytics-card-value">{{ $averageScore === null ? '—' : round($averageScore, 1).'%' }}</p>
            <p class="analytics-card-note">{{ $attemptCount ? 'Across all student attempts' : 'No scores recorded yet' }}</p>
            <div class="analytics-track" role="progressbar" aria-label="Average assessment score" aria-valuenow="{{ $averageScore ?? 0 }}" aria-valuemin="0" aria-valuemax="100"><span class="analytics-fill" style="width: {{ max(0, min(100, $averageScore ?? 0)) }}%"></span></div>
        </article>
        <article class="analytics-card">
            <h2 class="analytics-card-label">Code review coverage</h2>
            <p class="analytics-card-value">{{ $reviewRate }}%</p>
            <p class="analytics-card-note">{{ number_format($reviewedSubmissions) }} reviewed of {{ number_format($codeSubmissions) }} submissions</p>
            <div class="analytics-track" role="progressbar" aria-label="Code review coverage" aria-valuenow="{{ $reviewRate }}" aria-valuemin="0" aria-valuemax="100"><span class="analytics-fill" style="width: {{ $reviewRate }}%"></span></div>
        </article>
        <article class="analytics-card">
            <h2 class="analytics-card-label">Published coding exercises</h2>
            <p class="analytics-card-value">{{ number_format($publishedExercises) }}</p>
            <p class="analytics-card-note">Programming tasks available to students</p>
            <div class="analytics-card-footer"><span>Feedback messages sent</span><strong>{{ number_format($feedbackCount) }}</strong></div>
        </article>
    </section>

    <div class="analytics-grid">
        <section class="panel">
            <div class="panel-header"><div><h2>Assessment performance</h2><p>Average score for each of your assessments</p></div></div>
            <div class="analytics-bars">
                @forelse ($assessmentPerformance as $assessment)
                    <div class="analytics-bar-row">
                        <span class="analytics-bar-label" title="{{ $assessment->title }} ({{ $assessment->attempts_count }} attempts)">{{ $assessment->title }}</span>
                        <div class="analytics-track" aria-hidden="true"><span class="analytics-fill" style="width: {{ max(0, min(100, $assessment->average_score ?? 0)) }}%; background: var(--analytics-green)"></span></div>
                        <span class="analytics-bar-value">{{ $assessment->average_score === null ? '—' : round($assessment->average_score, 1).'%' }}</span>
                    </div>
                @empty
                    <div class="empty">No assessments yet.</div>
                @endforelse
            </div>
        </section>
        <section class="panel">
            <div class="panel-header"><div><h2>Lesson completions</h2><p>Completed students per lesson</p></div></div>
            <div class="analytics-bars">
                @forelse ($contentPerformance as $content)
                    <div class="analytics-bar-row">
                        <span class="analytics-bar-label" title="{{ $content->title }}">{{ $content->title }}</span>
                        <div class="analytics-track" role="progressbar" aria-label="{{ $content->title }} completions" aria-valuenow="{{ $content->completions_count }}" aria-valuemin="0" aria-valuemax="{{ $lessonMax }}"><span class="analytics-fill" style="width: {{ round($content->completions_count / $lessonMax * 100) }}%; background: var(--analytics-amber)"></span></div>
                        <span class="analytics-bar-value">{{ $content->completions_count }}</span>
                    </div>
                @empty
                    <div class="empty">No lessons yet.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="panel analytics-section">
        <div class="panel-header"><div><h2>Coding exercise activity</h2><p>Student submissions per exercise</p></div></div>
        <div class="analytics-bars">
            @forelse ($exercisePerformance as $exercise)
                <div class="analytics-bar-row">
                    <span class="analytics-bar-label" title="{{ $exercise->title }}">{{ $exercise->title }}</span>
                    <div class="analytics-track" role="progressbar" aria-label="{{ $exercise->title }} submissions" aria-valuenow="{{ $exercise->submissions_count }}" aria-valuemin="0" aria-valuemax="{{ $exerciseMax }}"><span class="analytics-fill" style="width: {{ round($exercise->submissions_count / $exerciseMax * 100) }}%"></span></div>
                    <span class="analytics-bar-value">{{ $exercise->submissions_count }}</span>
                </div>
            @empty
                <div class="empty">No coding exercises yet.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
