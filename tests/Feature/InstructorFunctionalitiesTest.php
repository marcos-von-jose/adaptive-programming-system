<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\CodeSubmission;
use App\Models\Exercise;
use App\Models\LearningContent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorFunctionalitiesTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $otherInstructor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $instructorRole = Role::create(['name' => 'Instructor']);
        Role::create(['name' => 'Administrator']);
        $studentRole = Role::create(['name' => 'Student']);
        $this->instructor = User::factory()->create(['role_id' => $instructorRole->id, 'password' => 'password']);
        $this->otherInstructor = User::factory()->create(['role_id' => $instructorRole->id]);
        $this->student = User::factory()->create(['role_id' => $studentRole->id]);
    }

    public function test_instructor_sidebar_links_open_their_pages(): void
    {
        $response = $this->actingAs($this->instructor)->get(route('instructor.dashboard'))->assertOk();
        foreach (['Manage Content', 'Monitor Student Progress', 'View Analytics', 'Manage Assessments', 'Provide Feedback', 'Profile Info'] as $label) {
            $response->assertSee($label);
        }
        foreach (['instructor.content.index', 'instructor.progress.index', 'instructor.analytics.index', 'instructor.assessments.index', 'instructor.feedback.index', 'instructor.profile.edit'] as $route) {
            $this->actingAs($this->instructor)->get(route($route))->assertOk();
            $this->actingAs($this->student)->get(route($route))->assertForbidden();
        }
        foreach (['instructor.content.create', 'instructor.assessments.create', 'instructor.feedback.create'] as $route) {
            $this->actingAs($this->instructor)->get(route($route))->assertOk();
        }
        $this->actingAs($this->instructor)->get(route('instructor.progress.show', $this->student))->assertOk();
    }

    public function test_instructor_can_manage_own_content_but_not_another_instructors_content(): void
    {
        $this->actingAs($this->instructor)->post(route('instructor.content.store'), [
            'title' => 'Loops', 'body' => 'Learn loops.', 'status' => 'published',
        ])->assertRedirect(route('instructor.content.index'));
        $own = LearningContent::where('title', 'Loops')->firstOrFail();
        $this->assertSame($this->instructor->id, $own->created_by);
        $other = LearningContent::create(['title' => 'Arrays', 'slug' => 'arrays', 'body' => 'Learn arrays.', 'status' => 'draft', 'created_by' => $this->otherInstructor->id]);

        $this->actingAs($this->instructor)->get(route('instructor.content.index'))
            ->assertOk()->assertSee('Loops')->assertDontSee('Arrays')
            ->assertDontSee('>View</a>', false);
        $this->actingAs($this->instructor)->get(route('instructor.content.edit', $other))->assertForbidden();
        $this->actingAs($this->instructor)->put(route('instructor.content.update', $other), [
            'title' => 'Changed', 'body' => 'Changed', 'status' => 'published',
        ])->assertForbidden();
        $this->actingAs($this->instructor)->delete(route('instructor.content.destroy', $other))->assertForbidden();
        $this->actingAs($this->instructor)->put(route('instructor.content.update', $own), [
            'title' => 'Loop Basics', 'body' => 'Learn loops.', 'status' => 'published',
        ])->assertRedirect(route('instructor.content.index'));
    }

    public function test_instructor_can_manage_own_assessments_and_view_own_analytics(): void
    {
        $this->actingAs($this->instructor)->post(route('instructor.assessments.store'), [
            'title' => 'Variables', 'status' => 'draft', 'passing_score' => 75,
        ])->assertRedirect();
        $assessment = Assessment::where('title', 'Variables')->firstOrFail();
        $this->actingAs($this->instructor)->post(route('instructor.assessments.questions.store', $assessment), [
            'prompt' => 'Choose the variable symbol', 'choices' => ['$', '#'], 'correct_choice' => 0,
        ])->assertSessionHas('success');
        $this->actingAs($this->instructor)->put(route('instructor.assessments.update', $assessment), [
            'title' => 'Variables', 'status' => 'published', 'passing_score' => 75,
        ])->assertRedirect(route('instructor.assessments.edit', $assessment));

        $other = Assessment::create(['title' => 'Other quiz', 'status' => 'draft', 'passing_score' => 70, 'created_by' => $this->otherInstructor->id]);
        $this->actingAs($this->instructor)->get(route('instructor.assessments.index'))->assertOk()->assertSee('Variables')->assertDontSee('Other quiz');
        $this->actingAs($this->instructor)->get(route('instructor.assessments.edit', $other))->assertForbidden();
        $this->actingAs($this->instructor)->post(route('instructor.assessments.questions.store', $other), [
            'prompt' => 'No access', 'choices' => ['A', 'B'], 'correct_choice' => 0,
        ])->assertForbidden();
        AssessmentAttempt::create(['assessment_id' => $assessment->id, 'student_id' => $this->student->id, 'score' => 100, 'completed_at' => now()]);
        $ownExercise = Exercise::create(['title' => 'My coding task', 'prompt' => 'Solve it.', 'status' => 'published', 'created_by' => $this->instructor->id]);
        $otherExercise = Exercise::create(['title' => 'Other coding task', 'prompt' => 'Solve it.', 'status' => 'published', 'created_by' => $this->otherInstructor->id]);
        foreach ([$ownExercise, $otherExercise] as $exercise) {
            CodeSubmission::create([
                'exercise_id' => $exercise->id, 'student_id' => $this->student->id,
                'source_code' => 'answer', 'status' => 'correct', 'submitted_at' => now(),
            ]);
        }
        $this->actingAs($this->instructor)->get(route('instructor.analytics.index'))
            ->assertOk()->assertSee('Variables')->assertSee('100%')->assertDontSee('Other quiz')
            ->assertSee('My coding task')->assertDontSee('Other coding task')
            ->assertViewHas('codeSubmissions', 1)
            ->assertViewHas('reviewedSubmissions', 1)
            ->assertViewHas('reviewRate', 100);
    }

    public function test_instructor_can_send_feedback_and_update_profile(): void
    {
        $this->actingAs($this->instructor)->post(route('instructor.feedback.store'), [
            'student_id' => $this->student->id, 'message' => 'Practice loops.',
        ])->assertRedirect(route('instructor.feedback.index'));
        $this->actingAs($this->instructor)->get(route('instructor.feedback.index'))->assertOk()->assertSee('Practice loops.');
        $this->actingAs($this->otherInstructor)->get(route('instructor.feedback.index'))->assertOk()->assertDontSee('Practice loops.');
        $this->actingAs($this->student)->get(route('student.dashboard'))->assertOk()->assertSee('Practice loops.');

        $this->actingAs($this->instructor)->put(route('instructor.profile.update'), [
            'name' => 'Updated Instructor', 'email' => 'updated-instructor@example.com',
            'current_password' => 'wrong', 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('current_password');
        $this->actingAs($this->instructor)->put(route('instructor.profile.update'), [
            'name' => 'Updated Instructor', 'email' => 'updated-instructor@example.com',
            'current_password' => 'password', 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('instructor.profile.edit'));
        $this->instructor->refresh();
        $this->assertSame('Updated Instructor', $this->instructor->name);
        $this->assertTrue(Hash::check('newpassword123', $this->instructor->password));
    }

    public function test_instructor_can_upload_and_replace_their_profile_photo(): void
    {
        Storage::fake('local');
        $this->actingAs($this->instructor)->get(route('instructor.profile.edit'))
            ->assertOk()->assertSee('enctype="multipart/form-data"', false)->assertSee('Profile photo');

        $this->actingAs($this->instructor)->put(route('instructor.profile.update'), [
            'name' => $this->instructor->name,
            'email' => $this->instructor->email,
            'profile_photo' => UploadedFile::fake()->image('instructor.png'),
        ])->assertRedirect(route('instructor.profile.edit'));

        $this->instructor->refresh();
        $firstPath = $this->instructor->profile_photo_path;
        Storage::disk('local')->assertExists($firstPath);
        $this->actingAs($this->instructor)->get(route('instructor.profile.edit'))
            ->assertOk()->assertSee('Your current profile photo')
            ->assertSee(route('users.photo', $this->instructor));
        $this->actingAs($this->instructor)->get(route('instructor.dashboard'))
            ->assertOk()->assertSee(route('users.photo', $this->instructor));

        $this->actingAs($this->instructor)->put(route('instructor.profile.update'), [
            'name' => $this->instructor->name,
            'email' => $this->instructor->email,
            'profile_photo' => UploadedFile::fake()->image('replacement.jpg'),
        ])->assertRedirect(route('instructor.profile.edit'));

        $this->instructor->refresh();
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($this->instructor->profile_photo_path);
    }
}
