<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SystemLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\UserProfileUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('role')
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('role'), fn ($query) => $query->where('role_id', $request->integer('role')))
            ->latest()
            ->paginate((int) SystemSetting::valueFor('items_per_page', 10))
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => Role::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.users.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'profile_photo' => UserProfileUpdater::PHOTO_RULES,
        ]);

        unset($validated['profile_photo']);
        $photoPath = $this->storePhoto($request);
        try {
            $user = User::create([...$validated, 'profile_photo_path' => $photoPath]);
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }
            throw $exception;
        }
        SystemLog::record('user.created', "Created user {$user->email}.", ['user_id' => $user->id]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'User created successfully.']);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['managedUser' => $user, 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(Request $request, User $user, UserProfileUpdater $profileUpdater): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'profile_photo' => UserProfileUpdater::PHOTO_RULES,
        ]);

        if ($request->user()->is($user) && (int) $validated['role_id'] !== $user->role_id) {
            throw ValidationException::withMessages(['role_id' => 'You cannot change your own administrator role.']);
        }

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        unset($validated['profile_photo']);
        $profileUpdater->update($user, $validated, $request->file('profile_photo'));
        SystemLog::record('user.updated', "Updated user {$user->email}.", ['user_id' => $user->id]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'User updated successfully.']);
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $email = $user->email;
        $id = $user->id;
        $photoPath = $user->profile_photo_path;
        $user->delete();
        if ($photoPath) {
            Storage::disk('local')->delete($photoPath);
        }
        SystemLog::record('user.deleted', "Deleted user {$email}.", ['user_id' => $id]);

        return back()->with('success', 'User deleted successfully.');
    }

    private function storePhoto(Request $request): ?string
    {
        if (! $request->hasFile('profile_photo')) {
            return null;
        }

        $path = $request->file('profile_photo')->store('user-photos', 'local');
        if ($path === false) {
            throw new RuntimeException('The profile photo could not be saved.');
        }

        return $path;
    }
}
