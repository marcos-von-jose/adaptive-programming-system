<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningContent;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\CodeSubmission;
use App\Models\Exercise;
use App\Models\LearningProgress;
use App\Models\StudentFeedback;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $totalUsers = User::count();
        $publishedContent = LearningContent::where('status', 'published')->count();
        $draftContent = LearningContent::where('status', 'draft')->count();
        $codeSubmissions = CodeSubmission::count();
        $reviewedSubmissions = CodeSubmission::whereIn('status', ['reviewed', 'correct', 'needs_retry'])->count();
        $assessmentAttempts = AssessmentAttempt::count();
        $passedAttempts = AssessmentAttempt::whereHas('assessment', fn ($query) => $query->whereColumn('assessment_attempts.score', '>=', 'assessments.passing_score'))->count();
        $registrations = User::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
        $registrationsByDate = $registrations->keyBy('date');

        return view('admin.reports.index', [
            'totalUsers' => $totalUsers,
            'newUsersThisMonth' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            'publishedContent' => $publishedContent,
            'draftContent' => $draftContent,
            'publishedAssessments' => Assessment::where('status', 'published')->count(),
            'publishedExercises' => Exercise::where('status', 'published')->count(),
            'codeSubmissions' => $codeSubmissions,
            'reviewedSubmissions' => $reviewedSubmissions,
            'reviewRate' => $codeSubmissions ? round($reviewedSubmissions / $codeSubmissions * 100) : 0,
            'assessmentAttempts' => $assessmentAttempts,
            'averageScore' => $assessmentAttempts ? round((float) AssessmentAttempt::avg('score'), 1) : null,
            'passedAttempts' => $passedAttempts,
            'passRate' => $assessmentAttempts ? round($passedAttempts / $assessmentAttempts * 100) : 0,
            'publicationRate' => $publishedContent + $draftContent ? round($publishedContent / ($publishedContent + $draftContent) * 100) : 0,
            'completedLessons' => LearningProgress::where('status', 'completed')->whereHas('content')->count(),
            'feedbackCount' => StudentFeedback::count(),
            'assessmentPerformance' => Assessment::withCount('attempts')->withAvg('attempts as average_score', 'score')->orderBy('title')->get(),
            'usersByRole' => Role::withCount('users')->orderBy('name')->get(),
            'registrations' => $registrations,
            'registrationChart' => collect(range(6, 0))->map(function ($daysAgo) use ($registrationsByDate) {
                $date = now()->subDays($daysAgo);

                return [
                    'label' => $date->format('D'),
                    'date' => $date->format('M j'),
                    'total' => (int) ($registrationsByDate->get($date->toDateString())?->total ?? 0),
                ];
            }),
        ]);
    }
}
