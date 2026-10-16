@extends('layouts.dashboard')
@section('title', 'Solve Exercise')
@section('content')
@include('admin.partials.alerts')
@if ($timedOut)
<div class="page-heading"><div><p class="eyebrow">Coding exercise</p><h1>{{ $exercise->title }}</h1></div></div>
<div class="notice error">The time limit has expired. Ask your instructor or administrator to reset your timer before trying again.</div>
<a class="button secondary" href="{{ route('student.exercises.index') }}">Back to exercises</a>
@else
<div data-fullscreen-activity data-activity-name="coding exercise"
    @if ($requiresTimer) data-timer-start-url="{{ route('student.exercises.timer.start', $exercise) }}" data-work-url="{{ route('student.exercises.work', $exercise) }}" data-csrf-token="{{ csrf_token() }}" @endif>
    <section class="panel" data-activity-gate>
        <h1>Ready to solve {{ $exercise->title }}?</h1>
        <p class="lead">Select Start coding to enter fullscreen. @if ($requiresTimer){{ $canShowWork ? 'Your timer is already running.' : 'Your timer begins when you start.' }} It continues if you exit fullscreen. @endif If you exit fullscreen before submitting, the editor will pause until you resume.</p>
        @if ($requiresTimer)<p class="form-help" data-gate-time hidden>Time remaining: <strong data-time-remaining>--:--</strong></p>@endif
        <p class="form-help" data-fullscreen-error role="alert" hidden></p>
        <button class="button" type="button" data-fullscreen-start>Start coding</button>
        <a class="button secondary" data-timer-return href="{{ route('student.exercises.index') }}" hidden>Back to exercises</a>
    </section>
    <div data-activity-content data-work-loaded="{{ $canShowWork ? '1' : '0' }}" hidden tabindex="-1">
        @if ($canShowWork) @include('student.exercises._work') @endif
    </div>
</div>
@endif
@endsection
