<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\CodeSubmission;
use App\Models\Exercise;
use App\Models\LearningContent;
use App\Models\ProgrammingLanguage;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentFunctionalitiesTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $otherStudent;

    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();
        $studentRole = Role::create(['name' => 'Student']);
        $instructorRole = Role::create(['name' => 'Instructor']);
        Role::create(['name' => 'Administrator']);
        $this->student = User::factory()->create(['role_id' => $studentRole->id, 'password' => 'password']);
        $this->otherStudent = User::factory()->create(['role_id' => $studentRole->id]);
        $this->instructor = User::factory()->create(['role_id' => $instructorRole->id]);
        $lesson = LearningContent::create([
            'title' => 'Prerequisite lesson', 'slug' => 'prerequisite-lesson',
            'body' => 'Study before activities.', 'status' => 'published',
        ]);
        $this->student->learningProgress()->create([
            'learning_content_id' => $lesson->id, 'status' => 'completed', 'completed_at' => now(),
        ]);
    }

    public function test_student_sidebar_pages_open_and_other_roles_are_denied(): void
    {
        $dashboard = $this->actingAs($this->student)->get(route('student.dashboard'))->assertOk();
        foreach (['Take Assessment', 'Access Learning Materials', 'Solve and Submit Code', 'View Progress', 'Receive Feedback', 'Profile Info'] as $label) {
            $dashboard->assertSee($label);
        }
        foreach (['student.assessments.index', 'student.learning.index', 'student.exercises.index', 'student.progress.index', 'student.feedback.index', 'student.profile.edit'] as $route) {
            $this->actingAs($this->student)->get(route($route))->assertOk();
            $this->actingAs($this->instructor)->get(route($route))->assertForbidden();
        }
    }

    public function test_old_safe_exam_browser_setting_no_longer_blocks_activities(): void
    {
        SystemSetting::create(['key' => 'seb_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'exam_security']);
        $assessment = Assessment::create(['title' => 'Open quiz', 'status' => 'published', 'passing_score' => 70]);
        $exercise = Exercise::create(['title' => 'Open exercise', 'prompt' => 'Write code.', 'status' => 'published']);

        $this->actingAs($this->student)->get(route('student.assessments.show', $assessment))->assertOk();
        $this->actingAs($this->student)->get(route('student.exercises.show', $exercise))->assertOk();
        $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), [
            'source_code' => 'print("hello")',
        ])->assertRedirect();
    }

    public function test_student_submits_code_and_only_sees_their_own_submission(): void
    {
        $exercise = Exercise::create([
            'title' => 'Hello World', 'prompt' => 'Print hello.', 'starter_code' => '<?php',
            'status' => 'published', 'published_at' => now(), 'created_by' => $this->instructor->id,
        ]);
        $draft = Exercise::create(['title' => 'Hidden', 'prompt' => 'Not available.', 'status' => 'draft', 'created_by' => $this->instructor->id]);

        $this->actingAs($this->student)->get(route('student.exercises.index'))->assertOk()->assertSee('Hello World')->assertDontSee('Hidden');
        $this->actingAs($this->student)->get(route('student.exercises.show', $exercise))
            ->assertOk()->assertSee('Print hello.')->assertSee('data-code-editor', false)
            ->assertSee('<div data-fullscreen-activity', false)->assertSee('data-work-loaded="1" hidden', false)
            ->assertSee('data-student-activity-locked', false)
            ->assertSee('name="source_language"', false);
        $this->actingAs($this->student)->get(route('student.exercises.show', $draft))->assertNotFound();
        $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), ['source_code' => ''])->assertSessionHasErrors('source_code');
        $source = "  print(\"hello\");\n";
        $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), [
            'source_code' => $source, 'source_language' => 'Rust',
        ])->assertRedirect();
        $submission = CodeSubmission::firstOrFail();
        $this->assertSame($this->student->id, $submission->student_id);
        $this->assertSame('submitted', $submission->status);
        $this->assertSame($source, $submission->source_code);
        $this->assertSame('Rust', $submission->source_language);
        $this->actingAs($this->student)->get(route('student.submissions.show', $submission))->assertOk()->assertSee('print(&quot;hello&quot;);', false);
        $this->actingAs($this->otherStudent)->get(route('student.submissions.show', $submission))->assertForbidden();
        $this->actingAs($this->student)->get(route('student.progress.index'))->assertOk()->assertSee('Hello World');
    }

    public function test_exercise_language_is_saved_even_if_submitted_language_is_changed(): void
    {
        $language = ProgrammingLanguage::create(['name' => 'Python', 'slug' => 'python', 'status' => 'active']);
        $exercise = Exercise::create([
            'title' => 'Greeting', 'prompt' => 'Print hello.', 'status' => 'published',
            'programming_language_id' => $language->id, 'created_by' => $this->instructor->id,
        ]);

        $this->actingAs($this->student)->get(route('student.exercises.show', $exercise))
            ->assertOk()->assertSee('data-language="python"', false)
            ->assertDontSee('name="source_language"', false);

        $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), [
            'source_code' => 'print("hello")', 'source_language' => 'JavaScript',
        ])->assertRedirect();

        $this->assertSame('Python', CodeSubmission::firstOrFail()->source_language);
    }

    public function test_completed_assessment_shows_a_review_instead_of_starting_again(): void
    {
        $assessment = Assessment::create(['title' => 'PHP Quiz', 'status' => 'published', 'passing_score' => 70]);
        $item = $assessment->items()->create(['prompt' => 'Which symbol starts a PHP variable?', 'position' => 1]);
        $wrong = $item->choices()->create(['text' => '#', 'is_correct' => false]);
        $item->choices()->create(['text' => '$', 'is_correct' => true]);

        $this->actingAs($this->student)->get(route('student.assessments.index'))
            ->assertOk()->assertSee('>Start</a>', false)->assertDontSee('>View Assessment</a>', false);

        $this->actingAs($this->student)->get(route('student.assessments.show', $assessment))
            ->assertOk()->assertSee('data-student-activity-locked', false)
            ->assertSee('<div data-fullscreen-activity', false)->assertSee('Start assessment');

        $this->actingAs($this->student)->post(route('student.assessments.store', $assessment), [
            'answers' => [$item->id => $wrong->id],
        ])->assertSessionHas('success');

        $this->actingAs($this->student)->get(route('student.assessments.index'))
            ->assertOk()->assertSee('>View Assessment</a>', false)->assertDontSee('>Start</a>', false);
        $this->actingAs($this->student)->get(route('student.assessments.show', $assessment))
            ->assertOk()->assertSee('Score: 0%')->assertSee('Your answer')->assertSee('Correct answer')
            ->assertDontSee('data-student-activity-locked', false)
            ->assertDontSee('<div data-fullscreen-activity', false)
            ->assertDontSee('Submit assessment');

        $this->actingAs($this->student)->post(route('student.assessments.store', $assessment), [
            'answers' => [$item->id => $wrong->id],
        ])->assertRedirect(route('student.assessments.show', $assessment));
        $this->assertSame(1, $assessment->attempts()->where('student_id', $this->student->id)->count());

        $this->actingAs($this->otherStudent)->get(route('student.assessments.index'))
            ->assertOk()->assertSee('Locked')->assertDontSee('>Start</a>', false);
    }

    public function test_submitted_exercise_links_to_the_students_latest_code(): void
    {
        $exercise = Exercise::create([
            'title' => 'Loops', 'prompt' => 'Write a loop.', 'status' => 'published',
            'created_by' => $this->instructor->id,
        ]);

        $this->actingAs($this->student)->get(route('student.exercises.index'))
            ->assertOk()->assertSee('>Solve</a>', false)->assertDontSee('View Submitted Code');

        $older = $this->student->codeSubmissions()->create([
            'exercise_id' => $exercise->id, 'source_code' => 'old solution',
            'status' => 'submitted', 'submitted_at' => now()->subDay(),
        ]);
        $latest = $this->student->codeSubmissions()->create([
            'exercise_id' => $exercise->id, 'source_code' => 'new solution',
            'status' => 'submitted', 'submitted_at' => now(),
        ]);

        $this->actingAs($this->student)->get(route('student.exercises.index'))
            ->assertOk()
            ->assertSee('href="'.route('student.submissions.show', $latest).'">View Submitted Code</a>', false)
            ->assertDontSee('href="'.route('student.submissions.show', $older).'">View Submitted Code</a>', false)
            ->assertDontSee('>Solve</a>', false);
        $this->actingAs($this->student)->get(route('student.submissions.show', $latest))
            ->assertOk()->assertSee('new solution');
        $this->actingAs($this->otherStudent)->get(route('student.exercises.index'))
            ->assertOk()->assertSee('Locked')->assertDontSee('>Solve</a>', false)->assertDontSee('View Submitted Code');
    }

    public function test_instructor_reviews_code_and_student_receives_feedback(): void
    {
        $exercise = Exercise::create(['title' => 'Loops', 'prompt' => 'Write a loop.', 'status' => 'published', 'created_by' => $this->instructor->id]);
        $submission = CodeSubmission::create(['exercise_id' => $exercise->id, 'student_id' => $this->student->id, 'source_code' => 'for (;;) {}', 'status' => 'submitted', 'submitted_at' => now()]);

        $this->actingAs($this->instructor)->get(route('instructor.progress.show', $this->student))->assertOk()->assertSee('for (;;) {}');
        $this->actingAs($this->instructor)->get(route('instructor.feedback.create', ['student_id' => $this->student->id, 'code_submission_id' => $submission->id]))->assertOk()->assertSee('Loops');
        $this->actingAs($this->instructor)->post(route('instructor.feedback.store'), [
            'student_id' => $this->otherStudent->id, 'code_submission_id' => $submission->id, 'message' => 'Keep practicing.',
        ])->assertSessionHasErrors('code_submission_id');
        $this->actingAs($this->instructor)->post(route('instructor.feedback.store'), [
            'student_id' => $this->student->id, 'code_submission_id' => $submission->id,
            'review_outcome' => 'incorrect', 'error_line' => 1,
            'error_message' => 'The loop has no exit condition.', 'message' => 'Add an exit condition.',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('code_submissions', ['id' => $submission->id, 'status' => 'needs_retry']);
        $this->actingAs($this->student)->get(route('student.feedback.index'))->assertOk()->assertSee('Add an exit condition.');
        $this->actingAs($this->student)->get(route('student.submissions.show', $submission))->assertOk()->assertSee('Add an exit condition.');
        $this->actingAs($this->otherStudent)->get(route('student.feedback.index'))->assertOk()->assertDontSee('Add an exit condition.');
    }

    public function test_wrong_answer_review_points_to_a_line_and_requires_a_new_attempt(): void
    {
        $exercise = Exercise::create([
            'title' => 'Add numbers', 'prompt' => 'Return the sum.', 'status' => 'published',
            'created_by' => $this->instructor->id,
        ]);
        $submission = $this->student->codeSubmissions()->create([
            'exercise_id' => $exercise->id,
            'source_code' => "function add(a, b) {\n  return a - b;\n  console.log('extra');\n}",
            'status' => 'submitted', 'submitted_at' => now(),
        ]);
        $reviewUrl = route('instructor.feedback.create', [
            'student_id' => $this->student->id, 'code_submission_id' => $submission->id,
        ]);

        $this->actingAs($this->instructor)->get($reviewUrl)->assertOk()
            ->assertSee('return a - b;')->assertSee('Mark line')->assertSee('Wrong answer')
            ->assertSee('name="error_lines[]"', false)
            ->assertSee('form="review-feedback-form"', false);

        $review = [
            'student_id' => $this->student->id,
            'code_submission_id' => $submission->id,
            'review_outcome' => 'incorrect',
            'error_lines' => [2, 3],
            'error_message' => 'Use addition and remove the extra output.',
            'message' => 'Fix lines 2 and 3, then submit again.',
        ];
        $this->actingAs($this->instructor)->post(route('instructor.feedback.store'), [
            ...$review, 'review_outcome' => '',
        ])->assertSessionHasErrors('review_outcome');
        $this->actingAs($this->instructor)->post(route('instructor.feedback.store'), [
            ...$review, 'error_message' => '',
        ])->assertSessionHasErrors('error_message');
        $this->actingAs($this->instructor)->post(route('instructor.feedback.store'), [
            ...$review, 'error_lines' => [2, 8],
        ])->assertSessionHasErrors('error_lines');
        $this->actingAs($this->instructor)->post(route('instructor.feedback.store'), $review)
            ->assertSessionHas('success');

        $this->assertSame('needs_retry', $submission->fresh()->status);
        $this->assertSame([2, 3], $submission->feedback()->firstOrFail()->markedErrorLines());
        $this->actingAs($this->instructor)->get(route('instructor.progress.show', $this->student))
            ->assertOk()->assertSee('Problem lines 2, 3')->assertSee('Use addition and remove the extra output.');
        $this->actingAs($this->student)->get(route('student.submissions.show', $submission))
            ->assertOk()->assertSee('>Retake</a>', false)->assertDontSee('Practice again')
            ->assertSee('Problem lines: 2, 3')
            ->assertSee('Use addition and remove the extra output.');
        $this->actingAs($this->student)->get(route('student.feedback.index'))
            ->assertOk()->assertSee('Problem lines 2, 3')->assertSee('Use addition and remove the extra output.');
        $this->actingAs($this->student)->get(route('student.dashboard'))
            ->assertOk()->assertSee('Problem lines 2, 3')->assertSee('View marked code');
        $this->actingAs($this->student)->get(route('student.exercises.show', $exercise))
            ->assertOk()->assertSee('Your latest answer needs another attempt.')
            ->assertSee('return a - b;');
        $this->actingAs($this->student)->get(route('student.exercises.index'))
            ->assertOk()->assertSee('>Retake</a>', false)->assertDontSee('Practice again');

        $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), [
            'source_code' => "function add(a, b) {\n  return a + b;\n}",
        ])->assertRedirect();
        $retry = CodeSubmission::latest('id')->firstOrFail();
        $this->assertNotSame($submission->id, $retry->id);
        $this->assertSame('submitted', $retry->status);
        $this->actingAs($this->student)->get(route('student.progress.index'))
            ->assertOk()->assertSee('return a - b;')->assertSee('return a + b;')
            ->assertSee('Previous attempt · inactive')->assertDontSee('>View</a>', false);
        $this->actingAs($this->student)->get(route('student.exercises.work', $exercise))
            ->assertOk()->assertSee('return a - b;')->assertSee('return a + b;')
            ->assertDontSee('>View</a>', false);
        $this->actingAs($this->student)->get(route('student.submissions.show', $submission))
            ->assertOk()->assertSee('This is a previous attempt.')
            ->assertDontSee('>Retake</a>', false)->assertDontSee('Practice again');

        $admin = User::factory()->create(['role_id' => Role::where('name', 'Administrator')->firstOrFail()->id]);
        $this->actingAs($admin)->get(route('admin.feedback.create', [
            'student_id' => $this->student->id, 'code_submission_id' => $retry->id,
        ]))->assertOk()->assertSee('return a + b;');
        $this->actingAs($admin)->get(route('admin.progress.show', $this->student))
            ->assertOk()->assertSee('Problem lines 2, 3');
        $this->actingAs($admin)->post(route('admin.feedback.store'), [
            'student_id' => $this->student->id,
            'code_submission_id' => $retry->id,
            'review_outcome' => 'correct',
            'message' => 'This solution is correct.',
        ])->assertSessionHas('success');
        $this->assertSame('correct', $retry->fresh()->status);
        $this->actingAs($this->student)->get(route('student.submissions.show', $retry))
            ->assertOk()->assertSee('Your answer was marked correct.')
            ->assertDontSee('Practice again')->assertDontSee('>Retake</a>', false);
        $this->actingAs($this->student)->get(route('student.exercises.index'))
            ->assertOk()->assertSee('View Submitted Code')
            ->assertDontSee('Practice again')->assertDontSee('>Retake</a>', false);

        $firstReview = $submission->feedback()->firstOrFail();
        $this->actingAs($admin)->get(route('admin.feedback.edit', $firstReview))
            ->assertOk()->assertSee('name="error_lines[]"', false);
        $this->actingAs($admin)->put(route('admin.feedback.update', $firstReview), [
            'message' => 'Only the extra output needs removal.',
            'review_outcome' => 'incorrect',
            'error_lines_present' => '1',
            'error_lines' => [3],
            'error_message' => 'Remove the extra output.',
        ])->assertRedirect(route('admin.feedback.index'));
        $this->assertSame([3], $firstReview->fresh()->markedErrorLines());
        $this->actingAs($admin)->put(route('admin.feedback.update', $firstReview), [
            'message' => 'This earlier answer is acceptable too.',
            'review_outcome' => 'correct',
        ])->assertRedirect(route('admin.feedback.index'));
        $this->assertSame('correct', $submission->fresh()->status);
        $this->actingAs($admin)->delete(route('admin.feedback.destroy', $firstReview))
            ->assertRedirect(route('admin.feedback.index'));
        $this->assertSame('submitted', $submission->fresh()->status);
    }

    public function test_student_can_complete_learning_material_and_update_profile(): void
    {
        $lesson = LearningContent::create(['title' => 'Variables', 'slug' => 'variables', 'body' => 'Learn variables.', 'status' => 'published']);
        $this->actingAs($this->student)->get(route('student.learning.show', $lesson))->assertOk()->assertSee('Learn variables.');
        $this->actingAs($this->student)->post(route('student.learning.complete', $lesson))->assertSessionHas('success');
        $this->actingAs($this->student)->get(route('student.progress.index'))->assertOk()->assertSee('Variables');

        $this->actingAs($this->student)->put(route('student.profile.update'), [
            'name' => 'Updated Student', 'email' => 'updated-student@example.com',
            'current_password' => 'wrong', 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('current_password');
        $this->actingAs($this->student)->put(route('student.profile.update'), [
            'name' => 'Updated Student', 'email' => 'updated-student@example.com',
            'current_password' => 'password', 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('student.profile.edit'));
        $this->student->refresh();
        $this->assertSame('Updated Student', $this->student->name);
        $this->assertTrue(Hash::check('newpassword123', $this->student->password));
    }

    public function test_student_can_upload_and_replace_their_profile_photo(): void
    {
        Storage::fake('local');
        $this->actingAs($this->student)->get(route('student.profile.edit'))
            ->assertOk()->assertSee('enctype="multipart/form-data"', false)->assertSee('Profile photo');

        $this->actingAs($this->student)->put(route('student.profile.update'), [
            'name' => $this->student->name,
            'email' => $this->student->email,
            'profile_photo' => UploadedFile::fake()->image('student.png'),
        ])->assertRedirect(route('student.profile.edit'));

        $this->student->refresh();
        $firstPath = $this->student->profile_photo_path;
        Storage::disk('local')->assertExists($firstPath);
        $this->actingAs($this->student)->get(route('student.profile.edit'))
            ->assertOk()->assertSee('Your current profile photo')
            ->assertSee(route('users.photo', $this->student));
        $this->actingAs($this->student)->get(route('student.dashboard'))
            ->assertOk()->assertSee(route('users.photo', $this->student));

        $this->actingAs($this->student)->put(route('student.profile.update'), [
            'name' => $this->student->name,
            'email' => $this->student->email,
            'profile_photo' => UploadedFile::fake()->image('replacement.jpg'),
        ])->assertRedirect(route('student.profile.edit'));

        $this->student->refresh();
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($this->student->profile_photo_path);

        $this->actingAs($this->student)->put(route('student.profile.update'), [
            'name' => $this->student->name,
            'email' => $this->student->email,
            'profile_photo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('profile_photo');
        Storage::disk('local')->assertExists($this->student->profile_photo_path);
    }

    public function test_instructor_can_create_exercises_but_cannot_edit_another_instructors_exercise(): void
    {
        $this->actingAs($this->instructor)->post(route('instructor.exercises.store'), [
            'title' => 'Arrays', 'prompt' => 'Return an array.', 'status' => 'published',
        ])->assertRedirect(route('instructor.exercises.index'));
        $exercise = Exercise::firstOrFail();
        $this->assertSame($this->instructor->id, $exercise->created_by);
        $otherInstructor = User::factory()->create(['role_id' => $this->instructor->role_id]);
        $this->actingAs($otherInstructor)->get(route('instructor.exercises.edit', $exercise))->assertForbidden();
        $this->actingAs($otherInstructor)->delete(route('instructor.exercises.destroy', $exercise))->assertForbidden();
    }
}
