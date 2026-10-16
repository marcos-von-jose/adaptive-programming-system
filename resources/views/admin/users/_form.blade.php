@php($editing = isset($managedUser))
<div class="form-grid">
    <div class="form-group"><label class="form-label" for="name">Full name</label><input class="field" id="name" name="name" value="{{ old('name', $managedUser->name ?? '') }}" required></div>
    <div class="form-group"><label class="form-label" for="email">Email address</label><input class="field" id="email" name="email" type="email" value="{{ old('email', $managedUser->email ?? '') }}" required></div>
    <div class="form-group"><label class="form-label" for="role_id">Role</label><select class="select" id="role_id" name="role_id" required><option value="">Select a role</option>@foreach ($roles as $role)<option value="{{ $role->id }}" @selected(old('role_id', $managedUser->role_id ?? '') == $role->id)>{{ $role->name }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label" for="profile_photo">Profile photo</label><input class="field" id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp"><span class="form-help">Optional. JPEG, PNG, or WebP, up to 2 MB.{{ $editing ? ' Leave blank to keep the current photo.' : '' }}</span></div>
    @if ($editing && $managedUser->profile_photo_path)
        <div class="form-group"><span class="form-label">Current photo</span><img class="user-photo-preview" src="{{ route('users.photo', $managedUser) }}" alt="Profile photo of {{ $managedUser->name }}"></div>
    @endif
    <div class="form-group"><label class="form-label" for="password">Password</label><input class="field" id="password" name="password" type="password" {{ $editing ? '' : 'required' }}><span class="form-help">{{ $editing ? 'Leave blank to keep the current password.' : 'Use at least 8 characters.' }}</span></div>
    <div class="form-group"><label class="form-label" for="password_confirmation">Confirm password</label><input class="field" id="password_confirmation" name="password_confirmation" type="password" {{ $editing ? '' : 'required' }}></div>
</div>
@unless ($inModal ?? false)
    <div class="form-actions"><a class="button secondary" href="{{ route('admin.users.index') }}">Cancel</a><button class="button" type="submit">{{ $editing ? 'Save changes' : 'Create user' }}</button></div>
@endunless
