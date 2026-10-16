<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.two-factor-settings', [
            'user' => $request->user(),
            'passwordConfirmed' => now()->timestamp - (int) $request->session()->get('auth.password_confirmed_at', 0)
                <= config('auth.password_timeout', 10800),
        ]);
    }
}
