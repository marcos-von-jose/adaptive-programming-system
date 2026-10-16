@extends('layouts.auth')

@section('title', 'Register')

@section('content')
    <h1 class="auth-title">Create an account</h1>
    <p class="auth-subtitle">Every new account is securely assigned the Student role. You must set up an authenticator app before using the system.</p>

    @if ($errors->any())
        <div class="alert alert-danger mb-4" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="name">Full name</label>
            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus>
        </div>

        <div class="mb-3">
            <label class="form-label" for="email">Email address</label>
            <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="auth-password-field">
                <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="new-password" required>
                <button class="auth-password-toggle" type="button" data-toggle-password aria-controls="password" aria-label="Show password" aria-pressed="false" title="Show password">
                    <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 6.1A10.8 10.8 0 0 1 12 6c6 0 9.5 6 9.5 6a14.8 14.8 0 0 1-3.1 3.5M6.1 7.5C3.8 9.2 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.7-.3 3.8-.9M10 10a3 3 0 0 0 4 4"/></svg>
                </button>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <div class="auth-password-field">
                <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                <button class="auth-password-toggle" type="button" data-toggle-password aria-controls="password_confirmation" aria-label="Show confirm password" aria-pressed="false" title="Show confirm password">
                    <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 6.1A10.8 10.8 0 0 1 12 6c6 0 9.5 6 9.5 6a14.8 14.8 0 0 1-3.1 3.5M6.1 7.5C3.8 9.2 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.7-.3 3.8-.9M10 10a3 3 0 0 0 4 4"/></svg>
                </button>
            </div>
        </div>

        <button class="btn auth-submit w-100" type="submit">Create student account</button>
    </form>

    <p class="auth-switch mt-4 mb-0">Already registered? <a href="{{ route('login') }}">Log in</a></p>
@endsection
