<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Support\UserProfileUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('instructor.profile.edit', ['instructor' => $request->user()]);
    }

    public function update(Request $request, UserProfileUpdater $profileUpdater): RedirectResponse
    {
        $instructor = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($instructor)],
            'current_password' => ['required_with:password', 'nullable', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'profile_photo' => UserProfileUpdater::PHOTO_RULES,
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        unset($data['current_password'], $data['profile_photo']);
        $profileUpdater->update($instructor, $data, $request->file('profile_photo'));
        SystemLog::record('profile.updated', 'Updated instructor profile.');

        return redirect()->route('instructor.profile.edit')->with('success', 'Profile updated.');
    }
}
