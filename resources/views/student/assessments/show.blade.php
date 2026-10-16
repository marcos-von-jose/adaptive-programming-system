@extends('layouts.dashboard')
@section('title', $attempt ? 'View Assessment' : 'Take Assessment')
@section('content')
@include('admin.partials.alerts')
@if ($attempt)
<div class="page-heading"><div><p class="eyebrow">Assessment result</p><h1>{{ $assessment->title }}</h1></div></div>
<section class="panel" style="margin-bottom:18px">
    <div class="panel-header"><h2>Your result</h2><span class="badge {{ $attempt->score >= $assessment->passing_score ? 'success' : 'warning' }}">{{ $attempt->score >= $assessment->passing_score ? 'Passed' : 'Keep practicing' }}</span></div>
    <p>Score: {{ $attempt->score }}% &middot; Completed {{ $attempt->completed_at?->format('M j, Y g:i A') }}</p>
</section>
@foreach ($assessment->items as $item)
    @php($response = $attempt->responses->firstWhere('assessment_item_id', $item->id))
    <section class="panel" style="margin-bottom:18px"><div class="panel-header"><h2>{{ $loop->iteration }}. {{ $item->prompt }}</h2></div><div class="list">
        @foreach ($item->choices as $choice)
            <div class="list-item"><span class="list-copy">{{ $choice->text }}</span>
                @if ($response?->assessment_choice_id === $choice->id)<span class="badge {{ $response->is_correct ? 'success' : 'warning' }}">Your answer</span>@endif
                @if ($choice->is_correct)<span class="badge success">Correct answer</span>@endif
            </div>
        @endforeach
    </div></section>
@endforeach
<div class="form-actions"><a class="button secondary" href="{{ route('student.assessments.index') }}">Back</a></div>
@elseif ($timedOut)
<div class="page-heading"><div><p class="eyebrow">Assessment</p><h1>{{ $assessment->title }}</h1></div></div>
<div class="notice error">The time limit has expired. Ask your instructor or administrator to reset your timer before trying again.</div>
<a class="button secondary" href="{{ route('student.assessments.index') }}">Back to assessments</a>
@else
<div data-fullscreen-activity data-activity-name="assessment"
    @if ($requiresTimer) data-timer-start-url="{{ route('student.assessments.timer.start', $assessment) }}" data-work-url="{{ route('student.assessments.work', $assessment) }}" data-csrf-token="{{ csrf_token() }}" @endif>
    <section class="panel" data-activity-gate>
        <h1>Ready to take {{ $assessment->title }}?</h1>
        <p class="lead">Select Start assessment to enter fullscreen. @if ($requiresTimer){{ $canShowWork ? 'Your timer is already running.' : 'Your timer begins when you start.' }} It continues if you exit fullscreen. @endif If you exit fullscreen before submitting, the assessment will pause until you resume.</p>
        @if ($requiresTimer)<p class="form-help" data-gate-time hidden>Time remaining: <strong data-time-remaining>--:--</strong></p>@endif
        <p class="form-help" data-fullscreen-error role="alert" hidden></p>
        <button class="button" type="button" data-fullscreen-start>Start assessment</button>
        <a class="button secondary" data-timer-return href="{{ route('student.assessments.index') }}" hidden>Back to assessments</a>
    </section>
    <div data-activity-content data-work-loaded="{{ $canShowWork ? '1' : '0' }}" hidden tabindex="-1">
        @if ($canShowWork) @include('student.assessments._work') @endif
    </div>
</div>
@endif
@endsection
