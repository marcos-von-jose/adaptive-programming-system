@extends('layouts.dashboard')
@section('title', 'Edit User')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">User management</p><h1>Edit user</h1><p class="lead">Update {{ $managedUser->name }}’s profile, role, or password.</p></div></div>
    @include('admin.partials.alerts')
    <section class="panel"><form method="POST" action="{{ route('admin.users.update', $managedUser) }}" enctype="multipart/form-data">@csrf @method('PUT') @include('admin.users._form')</form></section>
@endsection
