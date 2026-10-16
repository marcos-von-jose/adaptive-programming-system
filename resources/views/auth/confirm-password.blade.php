@extends('layouts.auth')

@section('title', 'Confirm your password')

@section('content')
    <h1 class="auth-title">Confirm your password</h1>
    <p class="auth-subtitle">Enter your current password before changing your two-factor authentication settings.</p>

    @if ($errors->any())
        <div class="alert alert-danger mb-4" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm.store') }}">
        @csrf
        <div class="mb-4">
            <label class="form-label" for="password">Current password</label>
            <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required autofocus>
        </div>
        <button class="btn auth-submit w-100" type="submit">Confirm password</button>
    </form>
@endsection
