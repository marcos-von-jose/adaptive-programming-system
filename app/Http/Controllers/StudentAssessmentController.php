<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\StudentActivitySession;
use App\Services\StudentActivityTimer;
use App\Services\LessonPrerequisites;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentAssessmentController extends Controller
{
    public function index(Request $request, LessonPrerequisites $prerequisites): View
    {
        $lessonStatus = $prerequisites->status($request->user());
        $assessments = Assessment::with('competency')->withCount('items')
            ->withCount(['attempts as my_attempts_count' => fn ($query) => $query->where('student_id', $request->user()->id)])
            ->withMax(['attempts as best_score' => fn ($query) => $query->where('student_id', $request->user()->id)], 'score')
            ->where('status', 'published')->orderBy('title')->get();
        return view('student.assessments.index', compact('assessments', 'lessonStatus'));
    }

    public function show(Request $request, Assessment $assessment, StudentActivityTimer $timer): View
    {
        abort_unless($assessment->status === 'published', 404);
        $assessment->load('items.choices');
        $attempt = $assessment->attempts()
            ->where('student_id', $request->user()->id)
            ->latest('completed_at')
            ->first();
        $attempt?->load('responses');

        $session = ! $attempt ? $timer->latest($request->user(), $assessment) : null;
        $requiresTimer = (bool) $assessment->time_limit_minutes || ($session && $session->completed_at === null);
        $timedOut = $session && ! $timer->isActive($session);
        $canShowWork = ! $requiresTimer || $timer->isActive($session);

        return view('student.assessments.show', compact('assessment', 'attempt', 'timedOut', 'canShowWork', 'requiresTimer'));
    }

    public function startTimer(Request $request, Assessment $assessment, StudentActivityTimer $timer): JsonResponse
    {
        abort_unless($assessment->status === 'published', 404);
        if ($assessment->attempts()->where('student_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'You have already completed this assessment.'], 409);
        }
        $existing = $timer->latest($request->user(), $assessment);
        if (! $assessment->time_limit_minutes && ! ($existing && $existing->completed_at === null)) {
            return response()->json(['remaining_milliseconds' => null]);
        }

        $session = $timer->start($request->user(), $assessment);
        if (! $timer->isActive($session)) {
            return response()->json(['message' => 'Time is up. Ask your instructor or administrator to reset the timer.'], 409);
        }

        return response()->json(['remaining_milliseconds' => $timer->remainingMilliseconds($session)]);
    }

    public function work(Request $request, Assessment $assessment, StudentActivityTimer $timer): View
    {
        abort_unless($assessment->status === 'published', 404);
        abort_if($assessment->attempts()->where('student_id', $request->user()->id)->exists(), 403);
        $session = $timer->latest($request->user(), $assessment);
        $requiresTimer = (bool) $assessment->time_limit_minutes || ($session && $session->completed_at === null);
        if ($requiresTimer) {
            abort_unless($timer->isActive($session), 403);
        }
        $assessment->load('items.choices');

        return view('student.assessments._work', compact('assessment', 'requiresTimer'));
    }

    public function store(Request $request, Assessment $assessment, StudentActivityTimer $timer): RedirectResponse
    {
        abort_unless($assessment->status === 'published', 404);
        if ($assessment->attempts()->where('student_id', $request->user()->id)->exists()) {
            return redirect()->route('student.assessments.show', $assessment)
                ->with('error', 'You have already completed this assessment.');
        }

        $session = $timer->latest($request->user(), $assessment);
        $requiresTimer = (bool) $assessment->time_limit_minutes || ($session && $session->completed_at === null);
        if ($requiresTimer && ! $timer->isActive($session)) {
            return redirect()->route('student.assessments.show', $assessment)
                ->with('error', 'Time is up or the assessment was not started. Ask your instructor to reset an expired timer.');
        }

        $assessment->load('items.choices');
        if ($assessment->items->isEmpty()) {
            return back()->with('error', 'This assessment has no questions yet.');
        }
        $data = $request->validate(['answers' => ['required', 'array']]);
        $answers = $data['answers'];
        foreach ($assessment->items as $item) {
            if (! isset($answers[$item->id]) || ! $item->choices->contains('id', (int) $answers[$item->id])) {
                return back()->withInput()->withErrors(['answers' => 'Answer every question with one of the listed choices.']);
            }
        }

        $correct = 0;
        DB::transaction(function () use ($assessment, $request, $answers, $session, $requiresTimer, &$correct): void {
            if ($requiresTimer) {
                $locked = StudentActivitySession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->completed_at === null && $locked->expires_at->isFuture(), 409, 'The assessment time limit has expired.');
            }
            $attempt = AssessmentAttempt::create([
                'assessment_id' => $assessment->id,
                'student_id' => $request->user()->id,
                'score' => 0,
                'completed_at' => now(),
            ]);
            foreach ($assessment->items as $item) {
                $choice = $item->choices->firstWhere('id', (int) $answers[$item->id]);
                $isCorrect = $choice->is_correct;
                $correct += (int) $isCorrect;
                $attempt->responses()->create([
                    'assessment_item_id' => $item->id,
                    'assessment_choice_id' => $choice->id,
                    'is_correct' => $isCorrect,
                ]);
            }
            $attempt->update(['score' => (int) round($correct / $assessment->items->count() * 100)]);
            if ($requiresTimer) {
                $locked->update(['completed_at' => now()]);
            }
        });

        return redirect()->route('student.assessments.index')->with('success', "Assessment submitted. You answered {$correct} of {$assessment->items->count()} questions correctly.");
    }
}
