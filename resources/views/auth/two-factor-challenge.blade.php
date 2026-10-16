@extends('layouts.auth')

@section('title', 'Two-factor authentication')

@section('content')
    <h1 class="auth-title">Verify it’s you</h1>
    <p class="auth-subtitle">Enter the current 6-digit code from your authenticator app to finish logging in.</p>

    @if ($errors->any())
        <div class="alert alert-danger mb-4" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.login.store') }}">
        @csrf
        <div class="mb-4">
            <label class="form-label" for="code">Authenticator code</label>
            <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus>
        </div>
        <button class="btn auth-submit w-100" type="submit">Verify and log in</button>
    </form>

    <details class="mt-4">
        <summary>Use a recovery code instead</summary>
        <form class="mt-3" method="POST" action="{{ route('two-factor.login.store') }}">
            @csrf
            <label class="form-label" for="recovery_code">Recovery code</label>
            <input class="form-control mb-3" id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code" required>
            <button class="btn auth-submit w-100" type="submit">Use recovery code</button>
        </form>
    </details>

    <p class="auth-switch mt-4 mb-0"><a href="{{ route('login') }}">Back to log in</a></p>
@endsection
