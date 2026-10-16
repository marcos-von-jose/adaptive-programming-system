<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentItem;
use App\Models\Competency;
use App\Models\SystemLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $assessments = Assessment::with('competency')
            ->withCount(['items', 'attempts'])
            ->when($this->isInstructor(), fn ($query) => $query->where('created_by', auth()->id()))
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->input('search').'%'))
            ->when(in_array($request->input('status'), ['draft', 'published'], true), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()->paginate((int) SystemSetting::valueFor('items_per_page', 10))->withQueryString();

        return view('admin.assessments.index', compact('assessments'));
    }

    public function create(): View
    {
        return view('admin.assessments.create', ['competencies' => Competency::orderBy('sequence')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateAssessment($request);
        if ($data['status'] === 'published') {
            return back()->withInput()->withErrors(['status' => 'Add at least one question before publishing.']);
        }
        $data['created_by'] = $request->user()->id;
        $assessment = Assessment::create($data);
        SystemLog::record('assessment.created', "Created assessment: {$assessment->title}.", ['assessment_id' => $assessment->id]);

        return redirect()->route($this->routeName('assessments.edit'), $assessment)->with('success', 'Assessment created. Add questions before publishing.');
    }

    public function edit(Assessment $assessment): View
    {
        $this->authorizeOwner($assessment);
        $assessment->load(['items.choices', 'competency'])->loadCount(['attempts']);
        return view('admin.assessments.edit', [
            'assessment' => $assessment,
            'competencies' => Competency::orderBy('sequence')->get(),
            'expiredSessions' => $assessment->timedSessions()->with('student')
                ->whereNull('completed_at')->where('expires_at', '<=', now())->latest('id')->get(),
        ]);
    }

    public function resetTimer(Assessment $assessment, User $student): RedirectResponse
    {
        $this->authorizeOwner($assessment);
        $removed = $assessment->timedSessions()->where('student_id', $student->id)
            ->whereNull('completed_at')->where('expires_at', '<=', now())->delete();
        abort_unless($removed > 0, 404);
        SystemLog::record('assessment.timer_reset', "Reset the assessment timer for {$student->name}.", [
            'assessment_id' => $assessment->id, 'student_id' => $student->id,
        ]);

        return back()->with('success', 'The student can start a new timed session.');
    }

    public function update(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwner($assessment);
        $data = $this->validateAssessment($request);
        if ($data['status'] === 'published' && ! $assessment->items()->exists()) {
            return back()->withInput()->withErrors(['status' => 'Add at least one question before publishing.']);
        }
        $assessment->update($data);
        SystemLog::record('assessment.updated', "Updated assessment: {$assessment->title}.", ['assessment_id' => $assessment->id]);
        return redirect()->route($this->routeName('assessments.edit'), $assessment)->with('success', 'Assessment updated.');
    }

    public function destroy(Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwner($assessment);
        if ($assessment->attempts()->exists()) {
            return back()->with('error', 'An assessment with student attempts cannot be deleted. Archive it as a draft instead.');
        }
        $title = $assessment->title;
        $assessment->delete();
        SystemLog::record('assessment.deleted', "Deleted assessment: {$title}.");
        return redirect()->route($this->routeName('assessments.index'))->with('success', 'Assessment deleted.');
    }

    public function storeItem(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwner($assessment);
        if ($assessment->attempts()->exists()) {
            return back()->with('error', 'Questions are locked after students have submitted attempts.');
        }
        $data = $this->validateItem($request);
        DB::transaction(function () use ($assessment, $data): void {
            $item = $assessment->items()->create(['prompt' => $data['prompt'], 'position' => ($assessment->items()->max('position') ?? 0) + 1]);
            foreach ($data['choices'] as $index => $choice) {
                $item->choices()->create(['text' => $choice, 'is_correct' => $index === (int) $data['correct_choice']]);
            }
        });
        SystemLog::record('assessment.question_created', "Added a question to {$assessment->title}.", ['assessment_id' => $assessment->id]);
        return back()->with('success', 'Question added.');
    }

    public function updateItem(Request $request, Assessment $assessment, AssessmentItem $item): RedirectResponse
    {
        $this->authorizeOwner($assessment);
        abort_unless($item->assessment_id === $assessment->id, 404);
        if ($assessment->attempts()->exists()) {
            return back()->with('error', 'Questions are locked after students have submitted attempts.');
        }
        $data = $this->validateItem($request);
        DB::transaction(function () use ($item, $data): void {
            $item->update(['prompt' => $data['prompt']]);
            $item->choices()->delete();
            foreach ($data['choices'] as $index => $choice) {
                $item->choices()->create(['text' => $choice, 'is_correct' => $index === (int) $data['correct_choice']]);
            }
        });
        SystemLog::record('assessment.question_updated', "Updated a question in {$assessment->title}.", ['assessment_id' => $assessment->id]);
        return back()->with('success', 'Question updated.');
    }

    public function destroyItem(Assessment $assessment, AssessmentItem $item): RedirectResponse
    {
        $this->authorizeOwner($assessment);
        abort_unless($item->assessment_id === $assessment->id, 404);
        if ($assessment->attempts()->exists()) {
            return back()->with('error', 'Questions are locked after students have submitted attempts.');
        }
        if ($assessment->status === 'published' && $assessment->items()->count() === 1) {
            return back()->with('error', 'A published assessment must retain at least one question.');
        }
        $item->delete();
        SystemLog::record('assessment.question_deleted', "Removed a question from {$assessment->title}.", ['assessment_id' => $assessment->id]);
        return back()->with('success', 'Question removed.');
    }

    private function validateAssessment(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'competency_id' => ['nullable', 'exists:competencies,id'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'passing_score' => ['required', 'integer', 'between:0,100'],
            'limit_hours' => ['nullable', 'integer', 'between:0,168'],
            'limit_minutes' => ['nullable', 'integer', 'between:0,59'],
        ]);
        $minutes = (int) ($data['limit_hours'] ?? 0) * 60 + (int) ($data['limit_minutes'] ?? 0);
        unset($data['limit_hours'], $data['limit_minutes']);
        $data['time_limit_minutes'] = $minutes ?: null;

        return $data;
    }

    private function validateItem(Request $request): array
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:2000'],
            'choices' => ['required', 'array', 'min:2', 'max:6'],
            'choices.*' => ['required', 'string', 'max:255'],
            'correct_choice' => ['required', 'integer', 'min:0'],
        ]);
        if (! array_key_exists((int) $data['correct_choice'], $data['choices'])) {
            throw ValidationException::withMessages(['correct_choice' => 'Select a valid correct answer.']);
        }
        return $data;
    }

    private function isInstructor(): bool
    {
        return request()->routeIs('instructor.*');
    }

    private function routeName(string $name): string
    {
        return ($this->isInstructor() ? 'instructor.' : 'admin.').$name;
    }

    private function authorizeOwner(Assessment $assessment): void
    {
        if ($this->isInstructor()) {
            abort_unless($assessment->created_by === auth()->id(), 403);
        }
    }
}
