@extends('layouts.dashboard')
@section('title', 'Add User')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">User management</p><h1>Add a user</h1><p class="lead">Create an account and grant the appropriate system role. The new user must set up an authenticator app before using the system.</p></div></div>
    @include('admin.partials.alerts')
    <section class="panel"><form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">@csrf @include('admin.users._form')</form></section>
@endsection
