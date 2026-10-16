@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@extends('layouts.dashboard')
@section('title', 'Edit Exercise')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Programming practice</p><h1>Edit coding exercise</h1><p class="lead">Update the task and its publication status.</p></div></div>
@include('admin.partials.alerts')
<section class="panel">
    <form method="POST" action="{{ route($managementPrefix.'.exercises.update', $exercise) }}">@csrf @method('PUT') @include('management.exercises._form')</form></section>
@if ($expiredSessions->isNotEmpty())
<section class="panel" style="margin-top:18px"><div class="panel-header"><h2>Expired student timers</h2></div><div class="list">
    @foreach ($expiredSessions as $session)
    <div class="list-item"><div class="list-copy"><div class="list-title">{{ $session->student->name }}</div><div class="list-subtitle">Expired {{ $session->expires_at->format('M j, Y g:i A') }}</div></div>
        <form method="POST" action="{{ route($managementPrefix.'.exercises.timers.reset', [$exercise, $session->student]) }}">@csrf<button class="button secondary small" type="submit">Reset timer</button></form>
    </div>
    @endforeach
</div></section>
@endif
@endsection
