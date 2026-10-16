@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@extends('layouts.dashboard')
@section('title', 'Provide Feedback')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Student guidance</p><h1>Provide feedback</h1><p class="lead">Send guidance to a student and review previous messages.</p></div><a class="button" href="{{ route($managementPrefix.'.feedback.create') }}">New feedback</a></div>
@include('admin.partials.alerts')
<section class="panel">
    <div class="table-wrap"><table>
        <thead><tr><th>Student</th><th>Feedback</th><th>Related work</th><th>Author</th><th>Date</th>@if ($managementPrefix === 'admin')<th style="text-align:right">Actions</th>@endif</tr></thead>
        <tbody>
            @forelse ($feedback as $entry)
                <tr>
                    <td><a href="{{ route($managementPrefix.'.progress.show', $entry->student) }}">{{ $entry->student?->name ?? 'Removed student' }}</a></td>
                    <td>{{ Str::limit($entry->message, 160) }}</td>
                    <td>{{ $entry->codeSubmission?->exercise?->title ?? $entry->attempt?->assessment?->title ?? 'General' }}</td>
                    <td>{{ $entry->author?->name ?? 'Former educator' }}</td>
                    <td>{{ $entry->created_at->format('M j, Y') }}</td>
                    @if ($managementPrefix === 'admin')
                        <td><div class="actions">
                            <a class="button secondary small" href="{{ route('admin.feedback.edit', $entry) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.feedback.destroy', $entry) }}" onsubmit="return confirm('Delete this feedback?')">@csrf @method('DELETE')<button class="button danger small" type="submit">Delete</button></form>
                        </div></td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $managementPrefix === 'admin' ? 6 : 5 }}"><div class="empty">No feedback sent yet.</div></td></tr>
            @endforelse
        </tbody>
    </table></div>
    @include('admin.partials.pagination', ['paginator' => $feedback])
</section>
@endsection
