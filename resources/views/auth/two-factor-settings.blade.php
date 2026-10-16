@extends('layouts.dashboard')

@section('title', 'Two-Factor Authentication')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Account security</p>
            <h1>Two-factor authentication</h1>
            <p class="lead">Use an authenticator app to generate a sign-in code. Fortify does not send these codes by email.</p>
        </div>
    </div>

    @if ($user->two_factor_required && (! filled($user->two_factor_secret) || ! filled($user->two_factor_confirmed_at)))
        <div class="notice warning" role="alert">Your account must finish two-factor authentication setup before you can use other pages. If you click another sidebar item, you will return here until you confirm an authenticator code.</div>
    @endif

    @if (session('status') === 'two-factor-authentication-enabled')
        <div class="notice">Scan the QR code with your authenticator app, then confirm a code to finish setup.</div>
    @elseif (session('status') === 'two-factor-authentication-confirmed')
        <div class="notice">Two-factor authentication is enabled for your account.</div>
    @elseif (session('status') === 'two-factor-authentication-disabled')
        <div class="notice">Two-factor authentication has been disabled.</div>
    @elseif (session('status') === 'recovery-codes-generated')
        <div class="notice">Your recovery codes have been regenerated.</div>
    @endif

    @if (session('error'))
        <div class="notice">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <ul class="validation">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif

    <section class="panel">
        @if (! $passwordConfirmed)
            <h2>Confirm your password to manage this setting</h2>
            <p class="lead">This extra check protects your authenticator setup and recovery codes.</p>
            <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-4">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="password">Current password</label>
                    <input class="field" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <div class="form-actions"><button class="button" type="submit">Confirm password</button></div>
            </form>
        @elseif (! $user->two_factor_secret)
            <h2>Protect your account with an authenticator app</h2>
            <p class="lead">Enable two-factor authentication to require a code from an app such as Google Authenticator, Microsoft Authenticator, or Authy whenever you sign in.</p>
            <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-4">
                @csrf
                <button class="button" type="submit">Enable two-factor authentication</button>
            </form>
        @elseif (! $user->two_factor_confirmed_at)
            <h2>Finish setting up your authenticator</h2>
            <p class="lead">Scan this QR code in your authenticator app, then enter the six-digit code it generates.</p>
            <div class="my-4" role="img" aria-label="Authenticator setup QR code">{!! $user->twoFactorQrCodeSvg() !!}</div>
            <form method="POST" action="{{ route('two-factor.confirm') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="code">Authenticator code</label>
                    <input class="field" id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
                </div>
                <div class="form-actions"><button class="button" type="submit">Confirm and enable</button></div>
            </form>
        @else
            <h2>Two-factor authentication is on</h2>
            <p class="lead">Your authenticator app is required each time you sign in. Keep these recovery codes somewhere safe; each code can be used once if you lose your device.</p>
            <div class="form-grid mt-4" aria-label="Recovery codes">
                @foreach ($user->recoveryCodes() as $code)
                    <code class="field">{{ $code }}</code>
                @endforeach
            </div>
            <div class="form-actions">
                <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                    @csrf
                    <button class="button secondary" type="submit">Generate new recovery codes</button>
                </form>
                @unless ($user->two_factor_required)
                    <form method="POST" action="{{ route('two-factor.disable') }}">
                        @csrf
                        @method('DELETE')
                        <button class="button danger" type="submit">Disable two-factor authentication</button>
                    </form>
                @endunless
            </div>
        @endif
    </section>
@endsection
