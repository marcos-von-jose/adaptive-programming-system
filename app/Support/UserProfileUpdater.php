<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UserProfileUpdater
{
    public const PHOTO_RULES = ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'];

    public function update(User $user, array $attributes, ?UploadedFile $photo): void
    {
        $newPhotoPath = $photo?->store('user-photos', 'local');
        if ($newPhotoPath === false) {
            throw new RuntimeException('The profile photo could not be saved.');
        }

        $oldPhotoPath = $user->profile_photo_path;
        if ($newPhotoPath) {
            $attributes['profile_photo_path'] = $newPhotoPath;
        }

        try {
            $user->update($attributes);
        } catch (Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('local')->delete($newPhotoPath);
            }
            throw $exception;
        }

        if ($newPhotoPath && $oldPhotoPath) {
            Storage::disk('local')->delete($oldPhotoPath);
        }
    }
}
