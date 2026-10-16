@extends('layouts.auth')

@section('title', 'Log in')

@section('content')
    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-subtitle">Log in with your password. If two-factor authentication is enabled, you’ll also enter a code from your authenticator app.</p>

    @if ($errors->any())
        <div class="alert alert-danger mb-4" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="email">Email address</label>
            <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $rememberedEmail) }}" autocomplete="username" required autofocus>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="auth-password-field">
                <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="current-password" required>
                <button class="auth-password-toggle" type="button" data-toggle-password aria-controls="password" aria-label="Show password" aria-pressed="false" title="Show password">
                    <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 6.1A10.8 10.8 0 0 1 12 6c6 0 9.5 6 9.5 6a14.8 14.8 0 0 1-3.1 3.5M6.1 7.5C3.8 9.2 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.7-.3 3.8-.9M10 10a3 3 0 0 0 4 4"/></svg>
                </button>
            </div>
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1" @checked(old('remember', $rememberedEmail !== null))>
            <label class="form-check-label" for="remember">Remember me</label>
        </div>

        <button class="btn auth-submit w-100" type="submit">Log in</button>
    </form>

    <p class="auth-switch mt-4 mb-0">No account yet? <a href="{{ route('register') }}">Register as a student</a></p>
@endsection
