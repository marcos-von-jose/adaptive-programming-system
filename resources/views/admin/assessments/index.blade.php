@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@extends('layouts.dashboard')
@section('title', 'Manage Assessments')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Learning evaluation</p><h1>Manage assessments</h1><p class="lead">Create question sets and publish them for students.</p></div><a class="button" href="{{ route($managementPrefix.'.assessments.create') }}">New assessment</a></div>
@include('admin.partials.alerts')
<section class="panel">
    <form class="toolbar" method="GET"><div class="filters"><input class="field" name="search" value="{{ request('search') }}" placeholder="Search assessments"><select class="select" name="status"><option value="">All statuses</option><option value="draft" @selected(request('status') === 'draft')>Draft</option><option value="published" @selected(request('status') === 'published')>Published</option></select><button class="button secondary">Filter</button></div></form>
    <div class="table-wrap"><table><thead><tr><th>Assessment</th><th>Competency</th><th>Questions</th><th>Attempts</th><th>Time limit</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead><tbody>
        @forelse ($assessments as $assessment)
        <tr><td><div class="cell-title">{{ $assessment->title }}</div><div class="cell-subtitle">{{ Str::limit($assessment->description, 60) }}</div></td><td>{{ $assessment->competency?->name ?? 'General' }}</td><td>{{ $assessment->items_count }}</td><td>{{ $assessment->attempts_count }}</td><td>{{ $assessment->time_limit_minutes ? $assessment->time_limit_minutes.' min' : 'No limit' }}</td><td><span class="badge {{ $assessment->status === 'published' ? 'success' : 'warning' }}">{{ $assessment->status }}</span></td><td><div class="actions"><a class="button secondary small" href="{{ route($managementPrefix.'.assessments.edit', $assessment) }}">Manage</a>@if ($assessment->attempts_count === 0)<form method="POST" action="{{ route($managementPrefix.'.assessments.destroy', $assessment) }}" onsubmit="return confirm('Delete this assessment and its questions?')">@csrf @method('DELETE')<button class="button danger small">Delete</button></form>@endif</div></td></tr>
        @empty <tr><td colspan="7"><div class="empty">No assessments found.</div></td></tr> @endforelse
    </tbody></table></div>
    @include('admin.partials.pagination', ['paginator' => $assessments])
</section>
@endsection
