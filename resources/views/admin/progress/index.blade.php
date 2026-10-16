@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@extends('layouts.dashboard')
@section('title', 'Student Progress')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Learning activity</p><h1>Monitor students progress</h1><p class="lead">Review lesson completions and assessment results for each student.</p></div></div>
<section class="panel"><form class="toolbar" method="GET"><div class="filters"><input class="field" name="search" value="{{ request('search') }}" placeholder="Search name or email"><button class="button secondary">Search</button></div></form><div class="table-wrap"><table><thead><tr><th>Student</th><th>Lessons completed</th><th>Assessments taken</th><th>Code submissions</th><th>Average score</th><th></th></tr></thead><tbody>
@forelse ($students as $student)<tr><td><div class="cell-title">{{ $student->name }}</div><div class="cell-subtitle">{{ $student->email }}</div></td><td>{{ $student->completed_lessons_count }}</td><td>{{ $student->assessment_attempts_count }}</td><td>{{ $student->code_submissions_count }}</td><td>{{ $student->average_score === null ? '—' : round($student->average_score, 1).'%' }}</td><td><div class="actions"><a class="button secondary small" href="{{ route($managementPrefix.'.progress.show', $student) }}">View progress</a></div></td></tr>
@empty <tr><td colspan="6"><div class="empty">No students found.</div></td></tr> @endforelse
</tbody></table></div>@include('admin.partials.pagination', ['paginator' => $students])</section>
@endsection
