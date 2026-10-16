<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\CodeSubmission;
use App\Models\Exercise;
use App\Models\LearningContent;
use App\Models\Role;
use App\Models\StudentFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLearningManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_assessment_and_student_attempt_appears_in_progress_and_analytics(): void
    {
        $admin = User::factory()->create(['role_id' => Role::create(['name' => 'Administrator'])->id]);
        $student = User::factory()->create(['role_id' => Role::create(['name' => 'Student'])->id]);
        $lesson = LearningContent::create([
            'title' => 'PHP lesson', 'slug' => 'php-lesson', 'body' => 'Learn PHP.', 'status' => 'published',
        ]);
        $student->learningProgress()->create([
            'learning_content_id' => $lesson->id, 'status' => 'completed', 'completed_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.assessments.store'), [
            'title' => 'PHP Basics', 'description' => 'Variables', 'status' => 'draft', 'passing_score' => 70,
        ])->assertRedirect();
        $assessment = Assessment::firstOrFail();
        $this->actingAs($admin)->put(route('admin.assessments.update', $assessment), [
            'title' => 'PHP Basics', 'status' => 'published', 'passing_score' => 70,
        ])->assertSessionHasErrors('status');

        $this->actingAs($admin)->post(route('admin.assessments.questions.store', $assessment), [
            'prompt' => 'What defines a PHP variable?',
            'choices' => ['$', '#', '@', '&'], 'correct_choice' => 0,
        ])->assertSessionHas('success');
        $this->actingAs($admin)->put(route('admin.assessments.update', $assessment), [
            'title' => 'PHP Basics', 'status' => 'published', 'passing_score' => 70,
        ])->assertSessionHas('success');

        $item = $assessment->items()->with('choices')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.assessments.questions.update', [$assessment, $item]), [
            'prompt' => 'Which symbol starts a PHP variable?',
            'choices' => ['$', '#', '@', '&'], 'correct_choice' => 0,
        ])->assertSessionHas('success');
        $item->refresh()->load('choices');
        $correct = $item->choices->firstWhere('is_correct', true);
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))->assertOk()->assertSee('Which symbol starts a PHP variable?');
        $this->actingAs($student)->post(route('student.assessments.store', $assessment), [
            'answers' => [$item->id => $correct->id],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('assessment_attempts', ['student_id' => $student->id, 'assessment_id' => $assessment->id, 'score' => 100]);
        $this->actingAs($admin)->get(route('admin.progress.show', $student))->assertOk()->assertSee('PHP Basics')->assertSee('100%');
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee('Assessment performance')->assertSee('PHP Basics');
        $this->actingAs($admin)->put(route('admin.assessments.questions.update', [$assessment, $item]), [
            'prompt' => 'Changed after attempt', 'choices' => ['A', 'B'], 'correct_choice' => 0,
        ])->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('admin.assessments.destroy', $assessment))->assertSessionHas('error');
    }

    public function test_feedback_must_target_a_student_and_their_own_attempt(): void
    {
        $admin = User::factory()->create(['role_id' => Role::create(['name' => 'Administrator'])->id]);
        $studentRole = Role::create(['name' => 'Student']);
        $student = User::factory()->create(['role_id' => $studentRole->id]);
        $other = User::factory()->create(['role_id' => $studentRole->id]);
        $assessment = Assessment::create(['title' => 'Quiz', 'status' => 'draft', 'passing_score' => 70]);
        $attempt = $other->assessmentAttempts()->create(['assessment_id' => $assessment->id, 'score' => 50, 'completed_at' => now()]);

        $this->actingAs($admin)->post(route('admin.feedback.store'), [
            'student_id' => $student->id, 'assessment_attempt_id' => $attempt->id, 'message' => 'Review loops.',
        ])->assertSessionHasErrors('assessment_attempt_id');
        $this->assertDatabaseCount('student_feedback', 0);

        $this->actingAs($admin)->post(route('admin.feedback.store'), [
            'student_id' => $student->id, 'message' => 'Review loops.',
        ])->assertSessionHas('success');
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk()->assertSee('Review loops.');
        $this->actingAs($other)->get(route('student.dashboard'))->assertOk()->assertDontSee('Review loops.');
    }

    public function test_admin_can_edit_and_delete_feedback_from_the_actions_column(): void
    {
        $admin = User::factory()->create(['role_id' => Role::create(['name' => 'Administrator'])->id]);
        $instructor = User::factory()->create(['role_id' => Role::create(['name' => 'Instructor'])->id]);
        $student = User::factory()->create(['role_id' => Role::create(['name' => 'Student'])->id]);
        $feedback = StudentFeedback::create([
            'student_id' => $student->id,
            'author_id' => $instructor->id,
            'message' => 'Practice this topic.',
        ]);

        $this->actingAs($admin)->get(route('admin.feedback.index'))
            ->assertOk()->assertSee('Actions')
            ->assertSee(route('admin.feedback.edit', $feedback))
            ->assertSee(route('admin.feedback.destroy', $feedback));
        $this->actingAs($instructor)->get(route('instructor.feedback.index'))
            ->assertOk()->assertDontSee(route('admin.feedback.edit', $feedback));
        $this->actingAs($instructor)->get(route('admin.feedback.edit', $feedback))->assertForbidden();
        $this->actingAs($instructor)->delete(route('admin.feedback.destroy', $feedback))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.feedback.edit', $feedback))
            ->assertOk()->assertSee('Practice this topic.');
        $this->actingAs($admin)->put(route('admin.feedback.update', $feedback), [
            'message' => '',
        ])->assertSessionHasErrors('message');
        $this->actingAs($admin)->put(route('admin.feedback.update', $feedback), [
            'message' => 'Review the examples again.',
        ])->assertRedirect(route('admin.feedback.index'));
        $this->actingAs($student)->get(route('student.feedback.index'))
            ->assertOk()->assertSee('Review the examples again.')->assertDontSee('Practice this topic.');

        $this->actingAs($admin)->delete(route('admin.feedback.destroy', $feedback))
            ->assertRedirect(route('admin.feedback.index'));
        $this->assertDatabaseMissing('student_feedback', ['id' => $feedback->id]);
        $this->actingAs($student)->get(route('student.feedback.index'))
            ->assertOk()->assertDontSee('Review the examples again.');
    }

    public function test_deleting_the_last_code_feedback_restores_submission_status(): void
    {
        $admin = User::factory()->create(['role_id' => Role::create(['name' => 'Administrator'])->id]);
        $student = User::factory()->create(['role_id' => Role::create(['name' => 'Student'])->id]);
        $exercise = Exercise::create(['title' => 'Loops', 'prompt' => 'Write a loop.', 'status' => 'published']);
        $submission = CodeSubmission::create([
            'exercise_id' => $exercise->id,
            'student_id' => $student->id,
            'source_code' => 'for (;;) {}',
            'status' => 'reviewed',
            'submitted_at' => now(),
        ]);
        $first = StudentFeedback::create([
            'student_id' => $student->id, 'author_id' => $admin->id,
            'code_submission_id' => $submission->id, 'message' => 'First review.',
        ]);
        $second = StudentFeedback::create([
            'student_id' => $student->id, 'author_id' => $admin->id,
            'code_submission_id' => $submission->id, 'message' => 'Second review.',
        ]);

        $this->actingAs($admin)->delete(route('admin.feedback.destroy', $first))->assertSessionHas('success');
        $this->assertSame('reviewed', $submission->fresh()->status);
        $this->actingAs($admin)->delete(route('admin.feedback.destroy', $second))->assertSessionHas('success');
        $this->assertSame('submitted', $submission->fresh()->status);
    }

    public function test_students_cannot_open_new_admin_pages(): void
    {
        $student = User::factory()->create(['role_id' => Role::create(['name' => 'Student'])->id]);
        foreach (['admin.assessments.index', 'admin.progress.index', 'admin.feedback.index', 'admin.reports.index'] as $route) {
            $this->actingAs($student)->get(route($route))->assertForbidden();
        }
    }

    public function test_published_lesson_completion_is_visible_in_admin_progress(): void
    {
        $admin = User::factory()->create(['role_id' => Role::create(['name' => 'Administrator'])->id]);
        $student = User::factory()->create(['role_id' => Role::create(['name' => 'Student'])->id]);
        $lesson = LearningContent::create([
            'title' => 'Loops', 'slug' => 'loops', 'body' => 'Practice loops.', 'status' => 'published', 'published_at' => now(),
        ]);

        $this->actingAs($student)->get(route('student.learning.index'))->assertOk()->assertSee('Loops');
        $this->actingAs($student)->post(route('student.learning.complete', $lesson))->assertSessionHas('success');
        $this->assertDatabaseHas('learning_progress', ['student_id' => $student->id, 'learning_content_id' => $lesson->id, 'status' => 'completed']);
        $this->actingAs($admin)->get(route('admin.progress.show', $student))->assertOk()->assertSee('Loops');
        $this->actingAs($admin)->get(route('admin.progress.index'))->assertOk()->assertSee('1');
    }

    public function test_management_forms_render(): void
    {
        $admin = User::factory()->create(['role_id' => Role::create(['name' => 'Administrator'])->id]);
        $assessment = Assessment::create(['title' => 'Quiz', 'status' => 'draft', 'passing_score' => 70]);
        $this->actingAs($admin)->get(route('admin.assessments.create'))->assertOk()->assertSee('Create assessment');
        $this->actingAs($admin)->get(route('admin.assessments.edit', $assessment))->assertOk()->assertSee('Add a question');
        $this->actingAs($admin)->get(route('admin.feedback.create'))->assertOk()->assertSee('New feedback');
    }
}
