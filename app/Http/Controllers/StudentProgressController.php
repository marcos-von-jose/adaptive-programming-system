<?php

namespace App\Http\Controllers;

use App\Models\LearningContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentProgressController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $progress = $student->learningProgress()
            ->where('status', 'completed')
            ->whereHas('content', fn ($query) => $query->where('status', 'published'))
            ->with('content')->latest()->get();
        $attempts = $student->assessmentAttempts()->with('assessment')->latest('completed_at')->get();
        $submissions = $student->codeSubmissions()->with('exercise')->latest('submitted_at')->latest('id')->get();
        $latestSubmissions = $submissions->unique('exercise_id');
        $latestSubmissionIds = $latestSubmissions->pluck('id', 'exercise_id');
        $publishedLessons = LearningContent::where('status', 'published')->count();
        $lessonPercent = $publishedLessons > 0
            ? min(100, round($progress->count() / $publishedLessons * 100))
            : 0;
        $passedAssessments = $attempts->filter(fn ($attempt) => $attempt->assessment && $attempt->score >= $attempt->assessment->passing_score)->count();
        $latestCodeTotal = $latestSubmissions->count();
        $latestCodeCorrect = $latestSubmissions->where('status', 'correct')->count();
        $latestCodeRetry = $latestSubmissions->where('status', 'needs_retry')->count();

        return view('student.progress.index', [
            'progress' => $progress,
            'attempts' => $attempts,
            'submissions' => $submissions,
            'latestSubmissionIds' => $latestSubmissionIds,
            'latestSubmissions' => $latestSubmissions,
            'publishedLessons' => $publishedLessons,
            'lessonPercent' => $lessonPercent,
            'assessmentAverage' => $attempts->isEmpty() ? null : round($attempts->avg('score'), 1),
            'passedAssessments' => $passedAssessments,
            'scoreChartAttempts' => $attempts->take(6)->reverse(),
            'latestCodeTotal' => $latestCodeTotal,
            'latestCodeCorrect' => $latestCodeCorrect,
            'latestCodeRetry' => $latestCodeRetry,
            'latestCodePending' => $latestCodeTotal - $latestCodeCorrect - $latestCodeRetry,
        ]);
    }
}
