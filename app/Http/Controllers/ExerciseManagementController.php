<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\ProgrammingLanguage;
use App\Models\SystemLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExerciseManagementController extends Controller
{
    public function index(Request $request): View
    {
        $exercises = Exercise::with(['programmingLanguage', 'author'])->withCount('submissions')
            ->when($this->isInstructor(), fn ($query) => $query->where('created_by', $request->user()->id))
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->input('search').'%'))
            ->latest()->paginate((int) SystemSetting::valueFor('items_per_page', 10))->withQueryString();
        return view('management.exercises.index', compact('exercises'));
    }

    public function create(): View
    {
        return view('management.exercises.create', ['languages' => $this->languages()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateExercise($request);
        $data['created_by'] = $request->user()->id;
        $data['published_at'] = $data['status'] === 'published' ? now() : null;
        $exercise = Exercise::create($data);
        SystemLog::record('exercise.created', "Created coding exercise: {$exercise->title}.", ['exercise_id' => $exercise->id]);
        return redirect()->route($this->routeName('exercises.index'))->with('success', 'Coding exercise created.');
    }

    public function edit(Exercise $exercise): View
    {
        $this->authorizeOwner($exercise);
        return view('management.exercises.edit', [
            'exercise' => $exercise,
            'languages' => $this->languages(),
            'expiredSessions' => $exercise->timedSessions()->with('student')
                ->whereNull('completed_at')->where('expires_at', '<=', now())->latest('id')->get(),
        ]);
    }

    public function resetTimer(Exercise $exercise, User $student): RedirectResponse
    {
        $this->authorizeOwner($exercise);
        $removed = $exercise->timedSessions()->where('student_id', $student->id)
            ->whereNull('completed_at')->where('expires_at', '<=', now())->delete();
        abort_unless($removed > 0, 404);
        SystemLog::record('exercise.timer_reset', "Reset the coding timer for {$student->name}.", [
            'exercise_id' => $exercise->id, 'student_id' => $student->id,
        ]);

        return back()->with('success', 'The student can start a new timed session.');
    }

    public function update(Request $request, Exercise $exercise): RedirectResponse
    {
        $this->authorizeOwner($exercise);
        $data = $this->validateExercise($request);
        $data['published_at'] = $data['status'] === 'published' ? ($exercise->published_at ?? now()) : null;
        $exercise->update($data);
        SystemLog::record('exercise.updated', "Updated coding exercise: {$exercise->title}.", ['exercise_id' => $exercise->id]);
        return redirect()->route($this->routeName('exercises.index'))->with('success', 'Coding exercise updated.');
    }

    public function destroy(Exercise $exercise): RedirectResponse
    {
        $this->authorizeOwner($exercise);
        if ($exercise->submissions()->exists()) {
            return back()->with('error', 'An exercise with student submissions cannot be deleted. Set it to draft instead.');
        }
        $title = $exercise->title;
        $exercise->delete();
        SystemLog::record('exercise.deleted', "Deleted coding exercise: {$title}.");
        return redirect()->route($this->routeName('exercises.index'))->with('success', 'Coding exercise deleted.');
    }

    private function validateExercise(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'prompt' => ['required', 'string', 'max:10000'],
            'starter_code' => ['nullable', 'string', 'max:50000'],
            'programming_language_id' => ['nullable', 'exists:programming_languages,id'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'limit_hours' => ['nullable', 'integer', 'between:0,168'],
            'limit_minutes' => ['nullable', 'integer', 'between:0,59'],
        ]);
        $minutes = (int) ($data['limit_hours'] ?? 0) * 60 + (int) ($data['limit_minutes'] ?? 0);
        unset($data['limit_hours'], $data['limit_minutes']);
        $data['time_limit_minutes'] = $minutes ?: null;

        return $data;
    }

    private function languages()
    {
        return ProgrammingLanguage::where('status', 'active')->orderBy('name')->get();
    }

    private function isInstructor(): bool
    {
        return request()->routeIs('instructor.*');
    }

    private function routeName(string $name): string
    {
        return ($this->isInstructor() ? 'instructor.' : 'admin.').$name;
    }

    private function authorizeOwner(Exercise $exercise): void
    {
        if ($this->isInstructor()) {
            abort_unless($exercise->created_by === auth()->id(), 403);
        }
    }
}
