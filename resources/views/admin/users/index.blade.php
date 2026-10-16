@extends('layouts.dashboard')
@section('title', 'Manage Users')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Administration</p><h1>Manage users</h1><p class="lead">Create accounts, assign roles, and maintain access to the system.</p></div><a class="button" href="{{ route('admin.users.create') }}" data-user-modal="user-create-template" data-user-modal-title="Add user" data-user-modal-confirm="Create user">Add user</a></div>
    @include('admin.partials.alerts')
    <section class="panel">
        <form class="toolbar" method="GET">
            <div class="filters"><input class="field" name="search" value="{{ request('search') }}" placeholder="Search name or email"><select class="select" name="role"><option value="">All roles</option>@foreach ($roles as $role)<option value="{{ $role->id }}" @selected(request('role') == $role->id)>{{ $role->name }}</option>@endforeach</select><button class="button secondary" type="submit">Filter</button></div>
        </form>
        <div class="table-wrap"><table><thead><tr><th>Photo</th><th>User</th><th>Role</th><th>Joined</th><th style="text-align:right">Actions</th></tr></thead><tbody>
            @forelse ($users as $managedUser)
                <tr>
                    <td class="user-photo-cell">
                        <span class="avatar">
                            @if ($managedUser->profile_photo_path)
                                <img src="{{ route('users.photo', $managedUser) }}" alt="Profile photo of {{ $managedUser->name }}">
                            @else
                                {{ collect(explode(' ', $managedUser->name))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join('') }}
                            @endif
                        </span>
                    </td>
                    <td><div class="cell-title">{{ $managedUser->name }}</div><div class="cell-subtitle">{{ $managedUser->email }}</div></td>
                    <td><span class="badge">{{ $managedUser->role?->name ?? 'No role' }}</span></td>
                    <td>{{ $managedUser->created_at?->format('M j, Y') }}</td>
                    <td><div class="actions"><button class="button secondary small" type="button" data-view-user data-user-id="{{ $managedUser->id }}" data-user-name="{{ $managedUser->name }}" data-user-email="{{ $managedUser->email }}" data-user-role="{{ $managedUser->role?->name ?? 'No role' }}" data-user-joined="{{ $managedUser->created_at?->format('M j, Y g:i A') ?? 'Unknown' }}" data-user-email-status="{{ $managedUser->email_verified_at ? 'Verified' : 'Not verified' }}" data-user-two-factor="{{ $managedUser->two_factor_confirmed_at ? 'Enabled' : ($managedUser->two_factor_required ? 'Required, not configured' : 'Not enabled') }}" @if ($managedUser->profile_photo_path) data-user-photo="{{ route('users.photo', $managedUser) }}" @endif>View</button><a class="button secondary small" href="{{ route('admin.users.edit', $managedUser) }}" data-user-modal="user-edit-template-{{ $managedUser->id }}" data-user-modal-title="Edit user" data-user-modal-confirm="Save changes">Edit</a><form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}" data-confirm-delete-user data-user-name="{{ $managedUser->name }}">@csrf @method('DELETE')<button class="button danger small" type="submit">Delete</button></form></div></td>
                </tr>
            @empty <tr><td colspan="5"><div class="empty">No users matched your search.</div></td></tr> @endforelse
        </tbody></table></div>
        @include('admin.partials.pagination', ['paginator' => $users])
    </section>
    <template id="user-create-template"><form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">@csrf @include('admin.users._form', ['managedUser' => null, 'inModal' => true])</form></template>
    @foreach ($users as $managedUser)
        <template id="user-edit-template-{{ $managedUser->id }}"><form method="POST" action="{{ route('admin.users.update', $managedUser) }}" enctype="multipart/form-data">@csrf @method('PUT') @include('admin.users._form', ['managedUser' => $managedUser, 'inModal' => true])</form></template>
    @endforeach
@endsection
