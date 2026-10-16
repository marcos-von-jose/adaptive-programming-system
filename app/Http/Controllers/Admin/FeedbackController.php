<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAttempt;
use App\Models\CodeSubmission;
use App\Models\StudentFeedback;
use App\Models\SystemLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $feedback = StudentFeedback::with(['student', 'author', 'attempt.assessment', 'codeSubmission.exercise'])
            ->when($request->routeIs('instructor.*'), fn ($query) => $query->where('author_id', $request->user()->id))
            ->when($request->filled('student_id'), fn ($query) => $query->where('student_id', $request->input('student_id')))
            ->latest()->paginate((int) SystemSetting::valueFor('items_per_page', 10))->withQueryString();

        return view('admin.feedback.index', compact('feedback'));
    }

    public function create(Request $request): View
    {
        $students = User::whereHas('role', fn ($query) => $query->where('name', 'Student'))->orderBy('name')->get(['id', 'name', 'email']);
        $studentId = $request->integer('student_id') ?: null;
        $attempts = $studentId ? AssessmentAttempt::with('assessment')->where('student_id', $studentId)->latest()->get() : collect();
        $submissions = $studentId ? CodeSubmission::with('exercise')->where('student_id', $studentId)->latest()->get() : collect();
        $selectedSubmission = $submissions->firstWhere('id', $request->integer('code_submission_id'));
        abort_if($request->filled('code_submission_id') && ! $selectedSubmission, 404);
        $selectedSubmission?->load('feedback.author');

        return view('admin.feedback.create', compact('students', 'studentId', 'attempts', 'submissions', 'selectedSubmission'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'assessment_attempt_id' => ['nullable', 'integer', 'exists:assessment_attempts,id'],
            'code_submission_id' => ['nullable', 'integer', 'exists:code_submissions,id'],
            'message' => ['required', 'string', 'max:5000'],
            'review_outcome' => ['nullable', Rule::in(['correct', 'incorrect'])],
            'error_line' => ['nullable', 'integer', 'min:1'],
            'error_lines' => ['nullable', 'array', 'max:1000'],
            'error_lines.*' => ['integer', 'min:1'],
            'error_message' => ['nullable', 'string', 'max:3000'],
        ]);
        $student = User::findOrFail($data['student_id']);
        if (! $student->hasRole('Student')) {
            return back()->withInput()->withErrors(['student_id' => 'Choose a student account.']);
        }
        if (! empty($data['assessment_attempt_id']) && ! AssessmentAttempt::whereKey($data['assessment_attempt_id'])->where('student_id', $student->id)->exists()) {
            return back()->withInput()->withErrors(['assessment_attempt_id' => 'That attempt does not belong to the selected student.']);
        }
        if (! empty($data['code_submission_id']) && ! CodeSubmission::whereKey($data['code_submission_id'])->where('student_id', $student->id)->exists()) {
            return back()->withInput()->withErrors(['code_submission_id' => 'That code submission does not belong to the selected student.']);
        }
        if (! empty($data['assessment_attempt_id']) && ! empty($data['code_submission_id'])) {
            return back()->withInput()->withErrors(['code_submission_id' => 'Link feedback to either an assessment or a code submission.']);
        }
        $submission = ! empty($data['code_submission_id']) ? CodeSubmission::findOrFail($data['code_submission_id']) : null;
        $lines = array_map('intval', $data['error_lines'] ?? []);
        if (isset($data['error_line'])) $lines[] = (int) $data['error_line'];
        $lines = array_values(array_unique($lines));
        sort($lines);
        if (! $submission && (! empty($data['review_outcome']) || $lines || ! empty($data['error_message']))) {
            return back()->withInput()->withErrors(['review_outcome' => 'Choose a code submission for a code review.']);
        }
        if ($submission && empty($data['review_outcome'])) {
            return back()->withInput()->withErrors(['review_outcome' => 'Mark the submitted answer correct or wrong.']);
        }
        if ($submission && ($data['review_outcome'] ?? null) === 'incorrect' && blank($data['error_message'] ?? null)) {
            return back()->withInput()->withErrors(['error_message' => 'Describe the error so the student knows what to fix.']);
        }
        if ($submission && $lines && max($lines) > count(preg_split('/\r\n|\r|\n/', $submission->source_code ?? ''))) {
            return back()->withInput()->withErrors(['error_lines' => 'Choose lines in the submitted code.']);
        }
        if (($data['review_outcome'] ?? null) !== 'incorrect') {
            $lines = [];
            $data['error_message'] = null;
        }
        $data['error_lines'] = $lines ?: null;
        $data['error_line'] = $lines[0] ?? null;
        $data['author_id'] = $request->user()->id;
        $feedback = DB::transaction(function () use ($data, $submission): StudentFeedback {
            $feedback = StudentFeedback::create($data);
            $submission?->syncReviewStatus();
            return $feedback;
        });
        SystemLog::record('feedback.created', "Provided feedback to {$student->name}.", ['feedback_id' => $feedback->id, 'student_id' => $student->id]);

        $route = $request->routeIs('instructor.*') ? 'instructor.feedback.index' : 'admin.feedback.index';
        return redirect()->route($route)->with('success', 'Feedback sent to the student.');
    }

    public function edit(StudentFeedback $feedback): View
    {
        $feedback->load(['student', 'attempt.assessment', 'codeSubmission.exercise', 'codeSubmission.feedback']);

        return view('admin.feedback.edit', compact('feedback'));
    }

    public function update(Request $request, StudentFeedback $feedback): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'review_outcome' => ['nullable', Rule::in(['correct', 'incorrect'])],
            'error_line' => ['nullable', 'integer', 'min:1'],
            'error_lines' => ['nullable', 'array', 'max:1000'],
            'error_lines.*' => ['integer', 'min:1'],
            'error_message' => ['nullable', 'string', 'max:3000'],
        ]);
        $submission = $feedback->codeSubmission;
        if ($submission && ! $request->exists('review_outcome')) $data['review_outcome'] = $feedback->review_outcome;
        if ($submission && ! $request->exists('error_message')) $data['error_message'] = $feedback->error_message;
        $lines = array_map('intval', $data['error_lines'] ?? []);
        if (isset($data['error_line'])) $lines[] = (int) $data['error_line'];
        if ($submission && ! $request->exists('error_lines') && ! $request->exists('error_line') && ! $request->boolean('error_lines_present')) {
            $lines = $feedback->markedErrorLines();
        }
        $lines = array_values(array_unique($lines));
        sort($lines);
        if (! $submission && (! empty($data['review_outcome']) || $lines || ! empty($data['error_message']))) {
            return back()->withInput()->withErrors(['review_outcome' => 'This feedback is not linked to code.']);
        }
        if ($submission && ($data['review_outcome'] ?? null) === 'incorrect' && blank($data['error_message'] ?? null)) {
            return back()->withInput()->withErrors(['error_message' => 'Describe the error so the student knows what to fix.']);
        }
        if ($submission && $lines && max($lines) > count(preg_split('/\r\n|\r|\n/', $submission->source_code ?? ''))) {
            return back()->withInput()->withErrors(['error_lines' => 'Choose lines in the submitted code.']);
        }
        if (($data['review_outcome'] ?? null) !== 'incorrect') {
            $lines = [];
            $data['error_message'] = null;
        }
        $data['error_lines'] = $lines ?: null;
        $data['error_line'] = $lines[0] ?? null;

        DB::transaction(function () use ($feedback, $data, $submission): void {
            $feedback->update($data);
            $submission?->syncReviewStatus();
        });
        SystemLog::record('feedback.updated', 'Updated student feedback.', [
            'feedback_id' => $feedback->id,
            'student_id' => $feedback->student_id,
        ]);

        return redirect()->route('admin.feedback.index')->with('success', 'Feedback updated successfully.');
    }

    public function destroy(StudentFeedback $feedback): RedirectResponse
    {
        $feedbackId = $feedback->id;
        $studentId = $feedback->student_id;
        $submissionId = $feedback->code_submission_id;

        DB::transaction(function () use ($feedback, $submissionId): void {
            $feedback->delete();

            if ($submissionId) CodeSubmission::find($submissionId)?->syncReviewStatus();
        });

        SystemLog::record('feedback.deleted', 'Deleted student feedback.', [
            'feedback_id' => $feedbackId,
            'student_id' => $studentId,
        ]);

        return redirect()->route('admin.feedback.index')->with('success', 'Feedback deleted successfully.');
    }
}
