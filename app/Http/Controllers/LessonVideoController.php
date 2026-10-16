<?php

namespace App\Http\Controllers;

use App\Models\LearningContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonVideoController extends Controller
{
    public function __invoke(Request $request, LearningContent $content): BinaryFileResponse
    {
        $user = $request->user();

        if ($user->hasRole('Student')) {
            abort_unless($content->status === 'published', 404);
        } else {
            abort_unless(
                $user->hasRole('Administrator')
                    || ($user->hasRole('Instructor') && $content->created_by === $user->id),
                403,
            );
        }

        abort_unless($content->video_path && Storage::disk('local')->exists($content->video_path), 404);

        return response()->file(Storage::disk('local')->path($content->video_path), [
            'Content-Type' => Storage::disk('local')->mimeType($content->video_path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
