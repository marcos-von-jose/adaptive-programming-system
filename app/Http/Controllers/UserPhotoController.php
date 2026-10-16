<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserPhotoController extends Controller
{
    public function __invoke(User $user): StreamedResponse
    {
        abort_unless(
            $user->profile_photo_path && Storage::disk('local')->exists($user->profile_photo_path),
            404,
        );

        $response = Storage::disk('local')->response($user->profile_photo_path);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
