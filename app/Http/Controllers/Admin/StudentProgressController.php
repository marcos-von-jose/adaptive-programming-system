<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentProgressController extends Controller
{
    public function index(Request $request): View
    {
        $students = User::whereHas('role', fn ($query) => $query->where('name', 'Student'))
            ->when($request->filled('search'), fn ($query) => $query->where(function ($inner) use ($request) {
                $search = '%'.$request->input('search').'%';
                $inner->where('name', 'like', $search)->orWhere('email', 'like', $search);
            }))
            ->withCount([
                'learningProgress as completed_lessons_count' => fn ($query) => $query->where('status', 'completed')
                    ->whereHas('content', fn ($content) => $content->where('status', 'published')),
                'assessmentAttempts',
                'codeSubmissions',
            ])
            ->withAvg('assessmentAttempts as average_score', 'score')
            ->orderBy('name')->paginate((int) SystemSetting::valueFor('items_per_page', 10))->withQueryString();

        return view('admin.progress.index', compact('students'));
    }

    public function show(User $student): View
    {
        abort_unless($student->hasRole('Student'), 404);
        $progress = $student->learningProgress()->whereHas('content')->with('content')->latest()->get();
        $attempts = $student->assessmentAttempts()->with('assessment')->latest('completed_at')->get();
        $submissions = $student->codeSubmissions()->with(['exercise', 'feedback'])->latest('submitted_at')->get();
        $feedback = $student->feedback()->with(['author', 'attempt.assessment'])->latest()->get();

        return view('admin.progress.show', compact('student', 'progress', 'attempts', 'submissions', 'feedback'));
    }
}
