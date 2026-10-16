@extends('layouts.dashboard')
@section('title', 'System Logs')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Audit trail</p><h1>System logs</h1><p class="lead">Review administrative changes and important system actions.</p></div><form method="POST" action="{{ route('admin.logs.clear') }}" data-confirm-clear-logs data-log-count="{{ $totalLogCount }}">@csrf @method('DELETE')<button class="button danger" type="submit" @disabled($totalLogCount === 0)>Clear all logs</button></form></div>
    @include('admin.partials.alerts')
    <section class="panel">
        <form class="toolbar" method="GET"><div class="filters"><input class="field" name="search" value="{{ request('search') }}" placeholder="Search activity"><select class="select" name="action"><option value="">All actions</option>@foreach ($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ str_replace('.', ' ', ucfirst($action)) }}</option>@endforeach</select><button class="button secondary" type="submit">Filter</button></div></form>
        <div class="table-wrap"><table><thead><tr><th>Activity</th><th>Administrator</th><th>IP address</th><th>Date</th></tr></thead><tbody>
            @forelse ($logs as $log)
                <tr>
                    <td><div class="cell-title">{{ $log->description }}</div><div class="cell-subtitle">{{ $log->action }}</div></td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td>{{ $log->ip_address ?: '—' }}</td>
                    <td><div>{{ $log->created_at->format('M j, Y') }}</div><div class="cell-subtitle">{{ $log->created_at->format('g:i A') }}</div></td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="empty">No system activity has been recorded yet.</div></td></tr>
            @endforelse
        </tbody></table></div>
        @include('admin.partials.pagination', ['paginator' => $logs])
    </section>
@endsection
