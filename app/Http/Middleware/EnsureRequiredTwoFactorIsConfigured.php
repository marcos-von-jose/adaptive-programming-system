<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequiredTwoFactorIsConfigured
{
    /**
     * Keep newly created accounts on the enrollment and sign-out paths until
     * they have confirmed an authenticator app. Required accounts cannot turn
     * two-factor authentication off again.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->two_factor_required) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName === 'two-factor.disable') {
            return redirect()
                ->route('account.two-factor.edit')
                ->with('error', 'Two-factor authentication is required for your account.');
        }

        if ($this->isEnrollmentComplete($user) || $this->isEnrollmentRoute($routeName)) {
            return $next($request);
        }

        return redirect()
            ->route('account.two-factor.edit')
            ->with('two_factor_required_notice', 'You must complete two-factor authentication before using this system page. Follow the setup steps below to unlock the sidebar and the rest of the system.');
    }

    private function isEnrollmentComplete(object $user): bool
    {
        return filled($user->two_factor_secret) && filled($user->two_factor_confirmed_at);
    }

    private function isEnrollmentRoute(?string $routeName): bool
    {
        return $routeName === 'account.two-factor.edit'
            || $routeName === 'password.confirm'
            || $routeName === 'password.confirm.store'
            || $routeName === 'logout'
            || in_array($routeName, [
                'two-factor.enable',
                'two-factor.confirm',
                'two-factor.qr-code',
                'two-factor.secret-key',
                'two-factor.recovery-codes',
                'two-factor.regenerate-recovery-codes',
            ], true);
    }
}
