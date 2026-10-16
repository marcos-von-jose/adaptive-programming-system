<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\CodeSubmission;
use App\Models\Exercise;
use App\Models\LearningContent;
use App\Models\LearningProgress;
use App\Models\StudentFeedback;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $instructorId = $request->user()->id;
        $attempts = AssessmentAttempt::whereHas('assessment', fn ($query) => $query->where('created_by', $instructorId));
        $attemptCount = (clone $attempts)->count();
        $passedCount = (clone $attempts)->whereHas('assessment', fn ($query) => $query->whereColumn('assessment_attempts.score', '>=', 'assessments.passing_score'))->count();
        $codeSubmissions = CodeSubmission::whereHas('exercise', fn ($query) => $query->where('created_by', $instructorId));
        $codeSubmissionCount = (clone $codeSubmissions)->count();
        $reviewedSubmissions = (clone $codeSubmissions)->whereIn('status', ['reviewed', 'correct', 'needs_retry'])->count();

        return view('instructor.analytics.index', [
            'publishedContent' => LearningContent::where('created_by', $instructorId)->where('status', 'published')->count(),
            'publishedAssessments' => Assessment::where('created_by', $instructorId)->where('status', 'published')->count(),
            'publishedExercises' => Exercise::where('created_by', $instructorId)->where('status', 'published')->count(),
            'codeSubmissions' => $codeSubmissionCount,
            'reviewedSubmissions' => $reviewedSubmissions,
            'reviewRate' => $codeSubmissionCount ? round($reviewedSubmissions / $codeSubmissionCount * 100) : 0,
            'attemptCount' => $attemptCount,
            'passedCount' => $passedCount,
            'passRate' => $attemptCount ? round($passedCount / $attemptCount * 100) : 0,
            'averageScore' => (clone $attempts)->avg('score'),
            'completedLessons' => LearningProgress::where('status', 'completed')->whereHas('content', fn ($query) => $query->where('created_by', $instructorId))->count(),
            'feedbackCount' => StudentFeedback::where('author_id', $instructorId)->count(),
            'assessmentPerformance' => Assessment::where('created_by', $instructorId)->withCount('attempts')->withAvg('attempts as average_score', 'score')->orderBy('title')->get(),
            'contentPerformance' => LearningContent::where('created_by', $instructorId)
                ->withCount(['learningProgress as completions_count' => fn ($query) => $query->where('status', 'completed')])
                ->orderBy('title')->get(),
            'exercisePerformance' => Exercise::where('created_by', $instructorId)->withCount('submissions')->orderBy('title')->get(),
        ]);
    }
}
