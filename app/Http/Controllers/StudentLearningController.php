<?php

namespace App\Http\Controllers;

use App\Models\LearningContent;
use App\Models\LearningProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentLearningController extends Controller
{
    public function index(Request $request): View
    {
        $contents = LearningContent::with('competency')->where('status', 'published')->orderBy('title')->get();
        $completedIds = $request->user()->learningProgress()->where('status', 'completed')->pluck('learning_content_id')->all();
        $remainingCount = $contents->pluck('id')->diff($completedIds)->count();
        return view('student.learning.index', compact('contents', 'completedIds', 'remainingCount'));
    }

    public function show(Request $request, LearningContent $content): View
    {
        abort_unless($content->status === 'published', 404);
        $completed = $request->user()->learningProgress()->where('learning_content_id', $content->id)->where('status', 'completed')->exists();
        return view('student.learning.show', compact('content', 'completed'));
    }

    public function complete(Request $request, LearningContent $content): RedirectResponse
    {
        abort_unless($content->status === 'published', 404);
        LearningProgress::updateOrCreate(
            ['student_id' => $request->user()->id, 'learning_content_id' => $content->id],
            ['status' => 'completed', 'completed_at' => now()],
        );
        return redirect()->route('student.learning.show', $content)->with('success', 'Lesson marked complete.');
    }
}
