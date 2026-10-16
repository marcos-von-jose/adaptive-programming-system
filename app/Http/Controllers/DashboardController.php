<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\StudentFeedback;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\CodeSubmission;
use App\Models\LearningContent;
use App\Models\Exercise;
use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        return match ($user->role?->name) {
            'Administrator' => redirect()->route('admin.dashboard'),
            'Instructor' => redirect()->route('instructor.dashboard'),
            'Student' => redirect()->route('student.dashboard'),
            default => abort(403, 'Your account does not have a valid role.'),
        };
    }

    public function admin(): View
    {
        $studentCount = User::whereHas('role', fn ($query) => $query->where('name', 'Student'))->count();
        $instructorCount = User::whereHas('role', fn ($query) => $query->where('name', 'Instructor'))->count();

        return view('dashboards.admin', [
            'totalUsers' => User::count(),
            'studentCount' => $studentCount,
            'instructorCount' => $instructorCount,
            'publishedContentCount' => LearningContent::where('status', 'published')->count(),
            'assessmentCount' => Assessment::count(),
            'attemptCount' => AssessmentAttempt::count(),
            'registrationTrend' => $this->dailyCounts(User::query(), 'created_at'),
            'recentLogs' => SystemLog::with('user')->latest()->take(4)->get(),
        ]);
    }

    public function instructor(Request $request): View
    {
        $instructorId = $request->user()->id;
        $ownAttempts = AssessmentAttempt::whereHas('assessment', fn ($query) => $query->where('created_by', $instructorId));

        return view('dashboards.instructor', [
            'contentCount' => LearningContent::where('created_by', $instructorId)->count(),
            'publishedContentCount' => LearningContent::where('created_by', $instructorId)->where('status', 'published')->count(),
            'exerciseCount' => Exercise::where('created_by', $instructorId)->count(),
            'assessmentCount' => Assessment::where('created_by', $instructorId)->count(),
            'studentCount' => User::whereHas('role', fn ($query) => $query->where('name', 'Student'))->count(),
            'attemptCount' => (clone $ownAttempts)->count(),
            'attemptTrend' => $this->dailyCounts(clone $ownAttempts, 'completed_at'),
            'recentSubmissions' => CodeSubmission::with(['student', 'exercise'])
                ->whereHas('exercise', fn ($query) => $query->where('created_by', $instructorId))
                ->latest('submitted_at')->take(4)->get(),
        ]);
    }

    public function student(Request $request): View
    {
        $student = $request->user();
        $publishedContentCount = LearningContent::where('status', 'published')->count();
        $completedLessons = $student->learningProgress()
            ->where('status', 'completed')
            ->whereHas('content', fn ($query) => $query->where('status', 'published'))
            ->count();

        return view('dashboards.student', [
            'completedLessons' => $completedLessons,
            'publishedContentCount' => $publishedContentCount,
            'attemptCount' => $student->assessmentAttempts()->count(),
            'submissionCount' => $student->codeSubmissions()->count(),
            'averageScore' => $student->assessmentAttempts()->avg('score'),
            'recentAttempts' => $student->assessmentAttempts()->with('assessment')->latest('completed_at')->take(6)->get()->reverse()->values(),
            'recentFeedback' => StudentFeedback::with('author')->where('student_id', $student->id)->latest()->take(3)->get(),
        ]);
    }

    private function dailyCounts(Builder $query, string $dateColumn): array
    {
        $start = now()->subDays(6)->startOfDay();
        $counts = $query->where($dateColumn, '>=', $start)
            ->selectRaw("DATE({$dateColumn}) as day, COUNT(*) as total")
            ->groupByRaw("DATE({$dateColumn})")
            ->pluck('total', 'day');

        return collect(range(0, 6))->map(function (int $offset) use ($start, $counts) {
            $day = $start->copy()->addDays($offset);

            return ['label' => $day->format('D'), 'date' => $day->format('M j'), 'value' => (int) ($counts[$day->toDateString()] ?? 0)];
        })->all();
    }
}
