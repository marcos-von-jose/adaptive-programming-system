@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@extends('layouts.dashboard')
@section('title', 'Manage Content')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Learning library</p><h1>Manage content</h1><p class="lead">Create and publish programming lessons and coding exercises.</p></div><div class="actions"><a class="button secondary" href="{{ route($managementPrefix.'.exercises.index') }}">Coding exercises</a><a class="button" href="{{ route($managementPrefix.'.content.create') }}">Add content</a></div></div>
    @include('admin.partials.alerts')
    <section class="panel">
        <form class="toolbar" method="GET"><div class="filters"><input class="field" name="search" value="{{ request('search') }}" placeholder="Search content"><select class="select" name="status"><option value="">All statuses</option><option value="draft" @selected(request('status') === 'draft')>Draft</option><option value="published" @selected(request('status') === 'published')>Published</option></select><button class="button secondary" type="submit">Filter</button></div></form>
        <div class="table-wrap"><table>
            <thead><tr><th>Content</th><th>Competency</th><th>Status</th><th>Author</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
                @forelse ($contents as $content)
                    <tr>
                        <td><div class="cell-title">{{ $content->title }}</div><div class="cell-subtitle">{{ Str::limit($content->summary ?: $content->body, 65) }}</div></td>
                        <td>{{ $content->competency?->name ?? 'General' }}</td>
                        <td><span class="badge {{ $content->status === 'published' ? 'success' : 'warning' }}">{{ $content->status }}</span></td>
                        <td>{{ $content->author?->name ?? 'System' }}</td>
                        <td><div class="actions">
                            <a class="button secondary small" href="{{ route($managementPrefix.'.content.edit', $content) }}">Edit</a>
                            <form method="POST" action="{{ route($managementPrefix.'.content.destroy', $content) }}" onsubmit="return confirm('Delete this content?')">@csrf @method('DELETE')<button class="button danger small" type="submit">Delete</button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty">No learning content found.</div></td></tr>
                @endforelse
            </tbody>
        </table></div>
        @include('admin.partials.pagination', ['paginator' => $contents])
    </section>
@endsection
