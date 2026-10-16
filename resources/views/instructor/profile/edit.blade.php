@extends('layouts.dashboard')
@section('title', 'Profile Info')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Your account</p><h1>Profile info</h1><p class="lead">Update your photo, name, email address, or password.</p></div></div>
@include('admin.partials.alerts')
<section class="panel"><form method="POST" action="{{ route('instructor.profile.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
    <div class="form-grid">
        <div class="form-group"><label class="form-label" for="name">Name</label><input class="field" id="name" name="name" value="{{ old('name', $instructor->name) }}" required></div>
        <div class="form-group"><label class="form-label" for="email">Email</label><input class="field" id="email" type="email" name="email" value="{{ old('email', $instructor->email) }}" required></div>
        <div class="form-group"><span class="form-label">Role</span><div class="field">{{ $instructor->role?->name }}</div></div>
        <div class="form-group"><span class="form-label">Member since</span><div class="field">{{ $instructor->created_at?->format('M j, Y') }}</div></div>
        @include('partials.profile-photo-input', ['profileUser' => $instructor])
        <div class="form-group full"><span class="form-help">Leave the password fields blank to keep your current password.</span></div>
        <div class="form-group"><label class="form-label" for="current_password">Current password</label><input class="field" id="current_password" type="password" name="current_password" autocomplete="current-password"></div>
        <div class="form-group"><label class="form-label" for="password">New password</label><input class="field" id="password" type="password" name="password" autocomplete="new-password"></div>
        <div class="form-group"><label class="form-label" for="password_confirmation">Confirm new password</label><input class="field" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"></div>
    </div><div class="form-actions"><button class="button">Save profile</button></div>
</form></section>
@endsection
