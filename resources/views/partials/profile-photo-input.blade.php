<div class="form-group full">
    <label class="form-label" for="profile_photo">Profile photo</label>
    @if ($profileUser->profile_photo_path)
        <img class="user-photo-preview" src="{{ route('users.photo', $profileUser) }}" alt="Your current profile photo">
    @endif
    <input class="field" id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp">
    <span class="form-help">Choose a JPEG, PNG, or WebP image up to 2 MB. Leave blank to keep your current photo.</span>
</div>
