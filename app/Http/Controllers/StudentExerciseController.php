<?php

namespace App\Http\Controllers;

use App\Models\CodeSubmission;
use App\Models\Exercise;
use App\Models\ProgrammingLanguage;
use App\Models\StudentActivitySession;
use App\Services\StudentActivityTimer;
use App\Services\LessonPrerequisites;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentExerciseController extends Controller
{
    public function index(Request $request, LessonPrerequisites $prerequisites): View
    {
        $lessonStatus = $prerequisites->status($request->user());
        $exercises = Exercise::with('programmingLanguage')
            ->withCount(['submissions as my_submissions_count' => fn ($query) => $query->where('student_id', $request->user()->id)])
            ->addSelect([
                'my_latest_submission_id' => CodeSubmission::query()
                    ->select('id')
                    ->whereColumn('exercise_id', 'exercises.id')
                    ->where('student_id', $request->user()->id)
                    ->latest('submitted_at')->latest('id')->limit(1),
                'my_latest_submission_status' => CodeSubmission::query()
                    ->select('status')
                    ->whereColumn('exercise_id', 'exercises.id')
                    ->where('student_id', $request->user()->id)
                    ->latest('submitted_at')->latest('id')->limit(1),
            ])
            ->where('status', 'published')->orderBy('title')->get();
        return view('student.exercises.index', compact('exercises', 'lessonStatus'));
    }

    public function show(Request $request, Exercise $exercise, StudentActivityTimer $timer): View
    {
        abort_unless($exercise->status === 'published', 404);
        $exercise->load('programmingLanguage');
        $languages = ProgrammingLanguage::where('status', 'active')->orderBy('name')->get(['name', 'slug']);
        $submissions = $request->user()->codeSubmissions()->where('exercise_id', $exercise->id)
            ->latest('submitted_at')->latest('id')->get();
        $retrySubmission = $submissions->first()?->status === 'needs_retry' ? $submissions->first() : null;
        $session = $timer->latest($request->user(), $exercise);
        $requiresTimer = (bool) $exercise->time_limit_minutes || ($session && $session->completed_at === null);
        $timedOut = $session && $session->completed_at === null && ! $timer->isActive($session);
        $canShowWork = ! $requiresTimer || $timer->isActive($session);

        return view('student.exercises.show', compact('exercise', 'languages', 'submissions', 'retrySubmission', 'timedOut', 'canShowWork', 'requiresTimer'));
    }

    public function startTimer(Request $request, Exercise $exercise, StudentActivityTimer $timer): JsonResponse
    {
        abort_unless($exercise->status === 'published', 404);
        $existing = $timer->latest($request->user(), $exercise);
        if (! $exercise->time_limit_minutes && ! ($existing && $existing->completed_at === null)) {
            return response()->json(['remaining_milliseconds' => null]);
        }

        $session = $timer->start($request->user(), $exercise);
        if (! $timer->isActive($session)) {
            return response()->json(['message' => 'Time is up. Ask your instructor or administrator to reset the timer.'], 409);
        }

        return response()->json(['remaining_milliseconds' => $timer->remainingMilliseconds($session)]);
    }

    public function work(Request $request, Exercise $exercise, StudentActivityTimer $timer): View
    {
        abort_unless($exercise->status === 'published', 404);
        $session = $timer->latest($request->user(), $exercise);
        $requiresTimer = (bool) $exercise->time_limit_minutes || ($session && $session->completed_at === null);
        if ($requiresTimer) {
            abort_unless($timer->isActive($session), 403);
        }
        $exercise->load('programmingLanguage');
        $languages = ProgrammingLanguage::where('status', 'active')->orderBy('name')->get(['name', 'slug']);
        $submissions = $request->user()->codeSubmissions()->where('exercise_id', $exercise->id)
            ->latest('submitted_at')->latest('id')->get();
        $retrySubmission = $submissions->first()?->status === 'needs_retry' ? $submissions->first() : null;

        return view('student.exercises._work', compact('exercise', 'languages', 'submissions', 'retrySubmission', 'requiresTimer'));
    }

    public function store(Request $request, Exercise $exercise, StudentActivityTimer $timer): RedirectResponse
    {
        abort_unless($exercise->status === 'published', 404);
        $session = $timer->latest($request->user(), $exercise);
        $requiresTimer = (bool) $exercise->time_limit_minutes || ($session && $session->completed_at === null);
        if ($requiresTimer && ! $timer->isActive($session)) {
            return redirect()->route('student.exercises.show', $exercise)
                ->with('error', 'Time is up or the exercise was not started. Ask your instructor to reset an expired timer.');
        }
        $data = $request->validate([
            'source_code' => ['required', 'string', 'max:100000'],
            'source_language' => ['nullable', 'string', 'max:100'],
        ]);
        $exercise->load('programmingLanguage');
        $submission = DB::transaction(function () use ($request, $exercise, $data, $session, $requiresTimer) {
            if ($requiresTimer) {
                $locked = StudentActivitySession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->completed_at === null && $locked->expires_at->isFuture(), 409, 'The coding time limit has expired.');
            }
            $submission = $request->user()->codeSubmissions()->create([
                'exercise_id' => $exercise->id,
                'source_code' => $data['source_code'],
                'source_language' => $exercise->programmingLanguage?->name ?? (trim($data['source_language'] ?? '') ?: null),
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
            if ($requiresTimer) {
                $locked->update(['completed_at' => now()]);
            }

            return $submission;
        });
        return redirect()->route('student.submissions.show', $submission)->with('success', 'Code submitted for review.');
    }

    public function submission(Request $request, CodeSubmission $submission): View
    {
        abort_unless($submission->student_id === $request->user()->id, 403);
        $submission->load(['exercise.programmingLanguage', 'feedback.author']);
        $latestSubmissionId = $request->user()->codeSubmissions()
            ->where('exercise_id', $submission->exercise_id)
            ->latest('submitted_at')->latest('id')->value('id');
        $isPreviousAttempt = $submission->id !== $latestSubmissionId;

        return view('student.exercises.submission', compact('submission', 'isPreviousAttempt'));
    }
}
