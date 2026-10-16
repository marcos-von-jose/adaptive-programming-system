<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use App\Support\UserProfileUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('student.profile.edit', ['student' => $request->user()]);
    }

    public function update(Request $request, UserProfileUpdater $profileUpdater): RedirectResponse
    {
        $student = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($student)],
            'current_password' => ['required_with:password', 'nullable', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'profile_photo' => UserProfileUpdater::PHOTO_RULES,
        ]);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        unset($data['current_password'], $data['profile_photo']);
        $profileUpdater->update($student, $data, $request->file('profile_photo'));
        SystemLog::record('profile.updated', 'Updated student profile.');
        return redirect()->route('student.profile.edit')->with('success', 'Profile updated.');
    }
}
