@extends('layouts.dashboard')
@section('title', 'My Progress')
@section('content')
<style>
    .student-progress { --progress-track: #e8edf2; --progress-accent: #df9145; --progress-green: #318464; --progress-blue: #4c78a8; --progress-red: #bb6a59; }
    .student-progress .page-heading { margin-bottom: 25px; }
    .progress-overview { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 18px; }
    .progress-card { min-width: 0; padding: 23px; border: 1px solid var(--line); border-radius: 20px; background: var(--paper); box-shadow: 0 12px 30px rgba(23, 32, 51, .035); }
    .progress-card-label { margin: 0 0 16px; color: var(--muted); font-size: 12px; font-weight: 750; }
    .progress-card-main { display: flex; align-items: center; gap: 18px; min-height: 106px; }
    .progress-ring { display: grid; width: 108px; height: 108px; flex: 0 0 108px; place-items: center; border-radius: 50%; background: conic-gradient(var(--progress-accent) var(--ring-value), var(--progress-track) 0); }
    .progress-ring-inner { display: grid; width: 84px; height: 84px; place-items: center; border-radius: 50%; background: var(--paper); }
    .progress-ring-inner strong { font-size: 24px; letter-spacing: -.05em; }
    .progress-card-number { margin: 0; font-size: 30px; font-weight: 800; letter-spacing: -.05em; line-height: 1.1; }
    .progress-card-caption { margin: 5px 0 0; color: var(--muted); font-size: 12px; line-height: 1.55; }
    .progress-card-foot { margin-top: 17px; padding-top: 14px; border-top: 1px solid var(--line); color: var(--muted); font-size: 11px; }
    .progress-card-foot strong { color: var(--ink); }
    .progress-track { height: 9px; overflow: hidden; border-radius: 999px; background: var(--progress-track); }
    .progress-fill { display: block; height: 100%; border-radius: inherit; background: var(--progress-accent); }
    .progress-average-track { margin-top: 20px; }
    .progress-average-track .progress-fill { background: var(--progress-blue); }
    .progress-segments { display: flex; gap: 3px; margin-top: 20px; }
    .progress-segments span { display: block; height: 9px; min-width: 0; }
    .progress-segments span:first-child { border-radius: 999px 0 0 999px; }
    .progress-segments span:last-child { border-radius: 0 999px 999px 0; }
    .progress-legend { display: flex; flex-wrap: wrap; gap: 7px 14px; margin-top: 12px; color: var(--muted); font-size: 11px; }
    .progress-legend span { display: inline-flex; align-items: center; gap: 6px; }
    .progress-legend i { width: 7px; height: 7px; border-radius: 50%; background: var(--legend-color); }
    .progress-section { margin-bottom: 18px; }
    .progress-section-subtitle { color: var(--muted); font-size: 11px; }
    .score-chart { display: grid; gap: 15px; margin-bottom: 23px; padding: 19px; border: 1px solid var(--line); border-radius: 14px; background: var(--canvas); }
    .score-chart-row { display: grid; grid-template-columns: minmax(110px, 190px) minmax(100px, 1fr) 48px; align-items: center; gap: 13px; }
    .score-chart-label { overflow: hidden; color: var(--ink); font-size: 12px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .score-chart .progress-track { height: 12px; }
    .score-chart .progress-fill { background: var(--progress-blue); }
    .score-chart-value { color: var(--ink); font-size: 12px; font-weight: 800; text-align: right; font-variant-numeric: tabular-nums; }
    .student-progress .table-wrap { margin-top: 4px; }
    .student-progress td.score-cell { min-width: 150px; }
    .student-progress .score-cell .progress-track { height: 5px; margin-top: 7px; }
    .student-progress .score-cell .progress-fill { background: var(--progress-blue); }
    html[data-theme="dark"] .student-progress { --progress-track: #34465b; --progress-accent: #f0b36b; --progress-green: #65be97; --progress-blue: #8eb2da; --progress-red: #db8c7e; }
    @media (max-width: 1100px) { .progress-overview { grid-template-columns: 1fr 1fr; } .progress-card:first-child { grid-column: 1 / -1; } }
    @media (max-width: 690px) { .progress-overview { grid-template-columns: 1fr; } .progress-card:first-child { grid-column: auto; } .score-chart { padding: 12px; } .score-chart-row { grid-template-columns: minmax(0, 90px) minmax(0, 1fr) 39px; gap: 8px; } }
    @media (max-width: 360px) { .progress-card { padding: 17px; } .progress-card-main { gap: 12px; } .progress-ring { width: 94px; height: 94px; flex-basis: 94px; } .progress-ring-inner { width: 72px; height: 72px; } .progress-ring-inner strong { font-size: 21px; } }
</style>
<div class="student-progress">
<div class="page-heading">
    <div>
        <p class="eyebrow">Learning history</p>
        <h1>View progress</h1>
        <p class="lead">Track your completed lessons, assessment results, and code submissions.</p>
    </div>
</div>
<section class="progress-overview" aria-label="Progress overview">
    <article class="progress-card">
        <h2 class="progress-card-label">Learning materials</h2>
        <div class="progress-card-main">
            <div class="progress-ring" role="progressbar" aria-label="Lessons completed" aria-valuenow="{{ $progress->count() }}" aria-valuemin="0" aria-valuemax="{{ max(1, $publishedLessons) }}" style="--ring-value: {{ $lessonPercent }}%">
                <div class="progress-ring-inner"><strong>{{ $lessonPercent }}%</strong></div>
            </div>
            <div>
                <p class="progress-card-number">{{ $progress->count() }} <span style="font-size:16px;color:var(--muted);font-weight:600">/ {{ $publishedLessons }}</span></p>
                <p class="progress-card-caption">Published lessons completed</p>
            </div>
        </div>
        <div class="progress-card-foot">{{ $publishedLessons === 0 ? 'No published lessons yet' : ($lessonPercent === 100 ? 'All published lessons completed' : ($publishedLessons - $progress->count()).' '.($publishedLessons - $progress->count() === 1 ? 'lesson' : 'lessons').' remaining') }}</div>
    </article>
    <article class="progress-card">
        <h2 class="progress-card-label">Assessment performance</h2>
        <p class="progress-card-number">{{ $assessmentAverage === null ? '—' : $assessmentAverage.'%' }}</p>
        <p class="progress-card-caption">{{ $assessmentAverage === null ? 'No scores recorded yet' : 'Average score across '.$attempts->count().' '.($attempts->count() === 1 ? 'attempt' : 'attempts') }}</p>
        <div class="progress-track progress-average-track" role="progressbar" aria-label="Average assessment score" aria-valuenow="{{ $assessmentAverage ?? 0 }}" aria-valuemin="0" aria-valuemax="100"><span class="progress-fill" style="width: {{ max(0, min(100, $assessmentAverage ?? 0)) }}%"></span></div>
        <div class="progress-card-foot"><strong>{{ $passedAssessments }}</strong> passed of {{ $attempts->count() }} taken</div>
    </article>
    <article class="progress-card">
        <h2 class="progress-card-label">Coding exercises</h2>
        <p class="progress-card-number">{{ $submissions->count() }}</p>
        <p class="progress-card-caption">Total submissions across {{ $latestCodeTotal }} {{ $latestCodeTotal === 1 ? 'exercise' : 'exercises' }}</p>
        @if ($latestCodeTotal)
            <div class="progress-segments" role="img" aria-label="Latest results: {{ $latestCodeCorrect }} correct, {{ $latestCodeRetry }} need retry, {{ $latestCodePending }} without a decision">
                @if ($latestCodeCorrect)<span style="flex: {{ $latestCodeCorrect }}; background: var(--progress-green)"></span>@endif
                @if ($latestCodeRetry)<span style="flex: {{ $latestCodeRetry }}; background: var(--progress-red)"></span>@endif
                @if ($latestCodePending)<span style="flex: {{ $latestCodePending }}; background: var(--progress-blue)"></span>@endif
            </div>
        @else
            <div class="progress-track progress-average-track"></div>
        @endif
        <div class="progress-legend">
            <span><i style="--legend-color:var(--progress-green)"></i>{{ $latestCodeCorrect }} correct</span>
            <span><i style="--legend-color:var(--progress-red)"></i>{{ $latestCodeRetry }} retry</span>
            <span><i style="--legend-color:var(--progress-blue)"></i>{{ $latestCodePending }} no decision</span>
        </div>
    </article>
</section>
<section class="panel progress-section">
    <div class="panel-header"><div><h2>Assessment results</h2><span class="progress-section-subtitle">Recent scores and complete attempt history</span></div></div>
    @if ($scoreChartAttempts->isNotEmpty())
        <div class="score-chart" aria-label="Last {{ $scoreChartAttempts->count() }} assessment scores">
            @foreach ($scoreChartAttempts as $attempt)
                <div class="score-chart-row">
                    <span class="score-chart-label" title="{{ $attempt->assessment?->title ?? 'Removed assessment' }}">{{ $attempt->assessment?->title ?? 'Removed assessment' }}</span>
                    <div class="progress-track" role="progressbar" aria-label="{{ $attempt->assessment?->title ?? 'Removed assessment' }} score" aria-valuenow="{{ $attempt->score }}" aria-valuemin="0" aria-valuemax="100"><span class="progress-fill" style="width: {{ max(0, min(100, $attempt->score)) }}%"></span></div>
                    <span class="score-chart-value">{{ $attempt->score }}%</span>
                </div>
            @endforeach
        </div>
    @endif
    <div class="table-wrap">
        <table>
            <thead><tr><th>Assessment</th><th>Score</th><th>Result</th><th>Date</th></tr></thead>
            <tbody>
                @forelse ($attempts as $attempt)
                    <tr>
                        <td>{{ $attempt->assessment?->title ?? 'Removed assessment' }}</td>
                        <td class="score-cell">{{ $attempt->score }}%<div class="progress-track" aria-hidden="true"><span class="progress-fill" style="width: {{ max(0, min(100, $attempt->score)) }}%"></span></div></td>
                        <td><span class="badge {{ $attempt->assessment && $attempt->score >= $attempt->assessment->passing_score ? 'success' : 'warning' }}">{{ $attempt->assessment && $attempt->score >= $attempt->assessment->passing_score ? 'Passed' : 'Keep practicing' }}</span></td>
                        <td>{{ $attempt->completed_at?->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty">No assessment attempts yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
<section class="panel progress-section">
    <div class="panel-header"><h2>Learning materials</h2></div>
    <div class="list">
        @forelse ($progress as $entry)
            <div class="list-item">
                <div class="list-copy">
                    <div class="list-title">{{ $entry->content?->title ?? 'Removed lesson' }}</div>
                    <div class="list-subtitle">Completed {{ $entry->completed_at?->format('M j, Y') }}</div>
                </div>
            </div>
        @empty
            <div class="empty">No lessons completed yet.</div>
        @endforelse
    </div>
</section>
<section class="panel">
    <div class="panel-header"><h2>Code submissions</h2></div>
    <div class="list">
        @forelse ($submissions as $submission)
            @php($isPrevious = ($latestSubmissionIds[$submission->exercise_id] ?? null) !== $submission->id)
            <article class="code-history-item {{ $isPrevious ? 'is-previous' : '' }}">
                <div class="code-history-heading">
                    <div>
                        <div class="list-title">{{ $submission->exercise?->title ?? 'Removed exercise' }}</div>
                        <div class="list-subtitle">{{ str_replace('_', ' ', ucfirst($submission->status)) }} &middot; {{ $submission->submitted_at?->format('M j, Y') }}</div>
                    </div>
                    <span class="badge {{ $isPrevious ? '' : 'success' }}">{{ $isPrevious ? 'Previous attempt · inactive' : 'Latest attempt' }}</span>
                </div>
                <pre class="code-history-code" aria-label="Submitted code">{{ $submission->source_code }}</pre>
            </article>
        @empty
            <div class="empty">No code submitted yet.</div>
        @endforelse
    </div>
</section>
</div>
@endsection
