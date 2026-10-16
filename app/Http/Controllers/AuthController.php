<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        abort_if(SystemSetting::valueFor('allow_registration', '1') !== '1', 404);

        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        abort_if(SystemSetting::valueFor('allow_registration', '1') !== '1', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $studentRole = Role::where('name', 'Student')->firstOrFail();

            return User::create([
                'role_id' => $studentRole->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard');
    }
}
