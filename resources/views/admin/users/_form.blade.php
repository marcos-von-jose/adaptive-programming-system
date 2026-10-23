@php($editing = isset($managedUser))
<div class="form-grid">
    <div class="form-group"><label class="form-label" for="name">Full name</label><input class="field" id="name" name="name" value="{{ old('name', $managedUser->name ?? '') }}" required></div>
    <div class="form-group"><label class="form-label" for="email">Email address</label><input class="field" id="email" name="email" type="email" value="{{ old('email', $managedUser->email ?? '') }}" required></div>
    <div class="form-group"><label class="form-label" for="role_id">Role</label><select class="select" id="role_id" name="role_id" required><option value="">Select a role</option>@foreach ($roles as $role)<option value="{{ $role->id }}" @selected(old('role_id', $managedUser->role_id ?? '') == $role->id)>{{ $role->name }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label" for="profile_photo">Profile photo</label><input class="field" id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp"><span class="form-help">Optional. JPEG, PNG, or WebP, up to 2 MB.{{ $editing ? ' Leave blank to keep the current photo.' : '' }}</span></div>
    @if ($editing && $managedUser->profile_photo_path)
        <div class="form-group"><span class="form-label">Current photo</span><img class="user-photo-preview" src="{{ route('users.photo', $managedUser) }}" alt="Profile photo of {{ $managedUser->name }}"></div>
    @endif
    @if ($editing)
        <section class="form-group full user-password-section" aria-labelledby="user-password-heading">
            <div class="user-password-heading"><h3 id="user-password-heading">Change password</h3><p>Leave both fields blank to keep the current password.</p></div>
            <div class="user-password-grid">
                <div class="form-group"><label class="form-label" for="password">New password</label><div class="admin-password-field"><input class="field" id="password" name="password" type="password" autocomplete="new-password"><button class="admin-password-toggle" type="button" data-admin-toggle-password aria-controls="password" aria-label="Show new password" aria-pressed="false" title="Show new password"><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 6.1A10.8 10.8 0 0 1 12 6c6 0 9.5 6 9.5 6a14.8 14.8 0 0 1-3.1 3.5M6.1 7.5C3.8 9.2 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.7-.3 3.8-.9M10 10a3 3 0 0 0 4 4"/></svg></button></div></div>
                <div class="form-group"><label class="form-label" for="password_confirmation">Confirm new password</label><div class="admin-password-field"><input class="field" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"><button class="admin-password-toggle" type="button" data-admin-toggle-password aria-controls="password_confirmation" aria-label="Show password confirmation" aria-pressed="false" title="Show password confirmation"><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 6.1A10.8 10.8 0 0 1 12 6c6 0 9.5 6 9.5 6a14.8 14.8 0 0 1-3.1 3.5M6.1 7.5C3.8 9.2 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.7-.3 3.8-.9M10 10a3 3 0 0 0 4 4"/></svg></button></div></div>
            </div>
        </section>
    @else
        <div class="form-group"><label class="form-label" for="password">Password</label><input class="field" id="password" name="password" type="password" autocomplete="new-password" required><span class="form-help">Use at least 8 characters.</span></div>
        <div class="form-group"><label class="form-label" for="password_confirmation">Confirm password</label><input class="field" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
    @endif
</div>
@unless ($inModal ?? false)
    <div class="form-actions"><a class="button secondary" href="{{ route('admin.users.index') }}">Cancel</a><button class="button" type="submit">{{ $editing ? 'Save changes' : 'Create user' }}</button></div>
@endunless
