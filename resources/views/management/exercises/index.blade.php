@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@extends('layouts.dashboard')
@section('title', 'Coding Exercises')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Programming practice</p><h1>Coding exercises</h1><p class="lead">Create tasks students can solve and submit for review.</p></div><a class="button" href="{{ route($managementPrefix.'.exercises.create') }}">New exercise</a></div>
@include('admin.partials.alerts')
<section class="panel"><form class="toolbar" method="GET"><div class="filters"><input class="field" name="search" value="{{ request('search') }}" placeholder="Search exercises"><button class="button secondary">Search</button></div></form><div class="table-wrap"><table><thead><tr><th>Exercise</th><th>Language</th><th>Submissions</th><th>Time limit</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead><tbody>
@forelse ($exercises as $exercise)<tr><td><div class="cell-title">{{ $exercise->title }}</div><div class="cell-subtitle">{{ Str::limit($exercise->prompt, 70) }}</div></td><td>{{ $exercise->programmingLanguage?->name ?? 'Any language' }}</td><td>{{ $exercise->submissions_count }}</td><td>{{ $exercise->time_limit_minutes ? $exercise->time_limit_minutes.' min' : 'No limit' }}</td><td><span class="badge {{ $exercise->status === 'published' ? 'success' : 'warning' }}">{{ $exercise->status }}</span></td><td><div class="actions"><a class="button secondary small" href="{{ route($managementPrefix.'.exercises.edit', $exercise) }}">Edit</a>@if ($exercise->submissions_count === 0)<form method="POST" action="{{ route($managementPrefix.'.exercises.destroy', $exercise) }}" onsubmit="return confirm('Delete this exercise?')">@csrf @method('DELETE')<button class="button danger small">Delete</button></form>@endif</div></td></tr>
@empty<tr><td colspan="6"><div class="empty">No coding exercises found.</div></td></tr>@endforelse
</tbody></table></div>@include('admin.partials.pagination', ['paginator' => $exercises])</section>
@endsection
