<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Exercise;
use App\Models\LearningContent;
use App\Models\Role;
use App\Models\StudentActivitySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimedActivitiesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $instructor;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role_id' => Role::create(['name' => 'Administrator'])->id]);
        $this->instructor = User::factory()->create(['role_id' => Role::create(['name' => 'Instructor'])->id]);
        $this->student = User::factory()->create(['role_id' => Role::create(['name' => 'Student'])->id]);
        $lesson = LearningContent::create([
            'title' => 'Prerequisite lesson', 'slug' => 'prerequisite-lesson',
            'body' => 'Study before activities.', 'status' => 'published',
        ]);
        $this->student->learningProgress()->create([
            'learning_content_id' => $lesson->id, 'status' => 'completed', 'completed_at' => now(),
        ]);
    }

    public function test_admin_and_instructor_can_set_hours_and_minutes(): void
    {
        $this->actingAs($this->admin)->post(route('admin.assessments.store'), [
            'title' => 'Timed quiz', 'status' => 'draft', 'passing_score' => 70,
            'limit_hours' => 1, 'limit_minutes' => 30,
        ])->assertSessionHasNoErrors();
        $assessment = Assessment::firstOrFail();
        $this->assertSame(90, $assessment->time_limit_minutes);
        $this->actingAs($this->admin)->get(route('admin.assessments.edit', $assessment))
            ->assertOk()->assertSee('name="limit_hours"', false)->assertSee('name="limit_minutes"', false);

        $this->actingAs($this->instructor)->post(route('instructor.exercises.store'), [
            'title' => 'Timed code', 'prompt' => 'Write a function.', 'status' => 'published',
            'limit_hours' => 0, 'limit_minutes' => 45,
        ])->assertSessionHasNoErrors();
        $exercise = Exercise::firstOrFail();
        $this->assertSame(45, $exercise->time_limit_minutes);
        $this->actingAs($this->instructor)->get(route('instructor.exercises.edit', $exercise))
            ->assertOk()->assertSee('name="limit_hours"', false)->assertSee('name="limit_minutes"', false);

        $this->actingAs($this->instructor)->put(route('instructor.exercises.update', $exercise), [
            'title' => 'Timed code', 'prompt' => 'Write a function.', 'status' => 'published',
            'limit_hours' => 0, 'limit_minutes' => 60,
        ])->assertSessionHasErrors('limit_minutes');
    }

    public function test_assessment_timer_starts_on_start_action_and_expiry_needs_reset(): void
    {
        $assessment = Assessment::create([
            'title' => 'Timed quiz', 'status' => 'published', 'passing_score' => 70,
            'time_limit_minutes' => 30, 'created_by' => $this->instructor->id,
        ]);
        $item = $assessment->items()->create(['prompt' => 'Secret question', 'position' => 1]);
        $choice = $item->choices()->create(['text' => 'Correct', 'is_correct' => true]);

        $this->actingAs($this->student)->get(route('student.assessments.show', $assessment))
            ->assertOk()->assertSee('Start assessment')->assertDontSee('Secret question');
        $this->actingAs($this->student)->get(route('student.assessments.work', $assessment))->assertForbidden();
        $this->actingAs($this->student)->post(route('student.assessments.store', $assessment), [
            'answers' => [$item->id => $choice->id],
        ])->assertSessionHas('error');

        $started = $this->actingAs($this->student)->postJson(route('student.assessments.timer.start', $assessment))
            ->assertOk();
        $this->assertGreaterThan(1798000, $started->json('remaining_milliseconds'));
        $this->assertLessThanOrEqual(1800000, $started->json('remaining_milliseconds'));
        $this->assertDatabaseCount('student_activity_sessions', 1);
        $this->actingAs($this->student)->get(route('student.assessments.work', $assessment))
            ->assertOk()->assertSee('Secret question');
        $this->actingAs($this->student)->get(route('student.assessments.show', $assessment))
            ->assertOk()->assertSee('Time remaining:')->assertSee('Secret question');

        $this->travel(10)->minutes();
        $resumed = $this->actingAs($this->student)->postJson(route('student.assessments.timer.start', $assessment))
            ->assertOk();
        $this->assertGreaterThan(1198000, $resumed->json('remaining_milliseconds'));
        $this->assertLessThanOrEqual(1200000, $resumed->json('remaining_milliseconds'));
        $this->assertDatabaseCount('student_activity_sessions', 1);

        $this->travel(21)->minutes();
        $this->actingAs($this->student)->post(route('student.assessments.store', $assessment), [
            'answers' => [$item->id => $choice->id],
        ])->assertSessionHas('error');
        $this->assertDatabaseCount('assessment_attempts', 0);
        $this->actingAs($this->student)->get(route('student.assessments.show', $assessment))
            ->assertOk()->assertSee('time limit has expired')->assertDontSee('Secret question');
        $this->actingAs($this->student)->postJson(route('student.assessments.timer.start', $assessment))->assertStatus(409);

        $this->actingAs($this->instructor)->get(route('instructor.assessments.edit', $assessment))
            ->assertOk()->assertSee('Expired student timers')->assertSee($this->student->name);
        $otherInstructor = User::factory()->create(['role_id' => $this->instructor->role_id]);
        $this->actingAs($otherInstructor)->post(route('instructor.assessments.timers.reset', [$assessment, $this->student]))
            ->assertForbidden();
        $this->actingAs($this->instructor)->post(route('instructor.assessments.timers.reset', [$assessment, $this->student]))
            ->assertSessionHas('success');
        $this->assertDatabaseCount('student_activity_sessions', 0);
        $this->actingAs($this->student)->postJson(route('student.assessments.timer.start', $assessment))->assertOk();
        $this->actingAs($this->student)->post(route('student.assessments.store', $assessment), [
            'answers' => [$item->id => $choice->id],
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('assessment_attempts', ['student_id' => $this->student->id, 'assessment_id' => $assessment->id]);
    }

    public function test_coding_timer_restarts_after_submission_but_expiry_needs_admin_reset(): void
    {
        $exercise = Exercise::create([
            'title' => 'Timed exercise', 'prompt' => 'Hidden task', 'status' => 'published',
            'time_limit_minutes' => 15, 'created_by' => $this->instructor->id,
        ]);
        $this->actingAs($this->student)->get(route('student.exercises.show', $exercise))
            ->assertOk()->assertSee('Start coding')->assertDontSee('Hidden task');
        $this->actingAs($this->student)->get(route('student.exercises.work', $exercise))->assertForbidden();
        $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), [
            'source_code' => 'print(1)',
        ])->assertSessionHas('error');

        $this->actingAs($this->student)->postJson(route('student.exercises.timer.start', $exercise))->assertOk();
        $this->actingAs($this->student)->get(route('student.exercises.work', $exercise))->assertOk()->assertSee('Hidden task');
        $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), [
            'source_code' => 'print(1)',
        ])->assertRedirect();
        $this->assertDatabaseCount('code_submissions', 1);
        $this->assertNotNull(StudentActivitySession::firstOrFail()->completed_at);

        $this->actingAs($this->student)->postJson(route('student.exercises.timer.start', $exercise))->assertOk();
        $this->assertDatabaseCount('student_activity_sessions', 2);
        $this->travel(16)->minutes();
        $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), [
            'source_code' => 'print(2)',
        ])->assertSessionHas('error');
        $this->assertDatabaseCount('code_submissions', 1);
        $this->actingAs($this->student)->get(route('student.exercises.show', $exercise))
            ->assertOk()->assertSee('time limit has expired')->assertDontSee('Hidden task');

        $this->actingAs($this->admin)->get(route('admin.exercises.edit', $exercise))
            ->assertOk()->assertSee('Expired student timers');
        $this->actingAs($this->admin)->post(route('admin.exercises.timers.reset', [$exercise, $this->student]))
            ->assertSessionHas('success');
        $this->actingAs($this->student)->postJson(route('student.exercises.timer.start', $exercise))->assertOk();
        $this->assertDatabaseCount('student_activity_sessions', 2);
    }

    public function test_changing_limit_to_unlimited_does_not_cancel_a_started_deadline(): void
    {
        $exercise = Exercise::create([
            'title' => 'Changed exercise', 'prompt' => 'Write code.', 'status' => 'published',
            'time_limit_minutes' => 1, 'created_by' => $this->instructor->id,
        ]);
        $this->actingAs($this->student)->postJson(route('student.exercises.timer.start', $exercise))->assertOk();
        $exercise->update(['time_limit_minutes' => null]);
        $this->travel(2)->minutes();

        $this->actingAs($this->student)->get(route('student.exercises.show', $exercise))
            ->assertOk()->assertSee('time limit has expired');
        $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), [
            'source_code' => 'print(1)',
        ])->assertSessionHas('error');
        $this->assertDatabaseCount('code_submissions', 0);

        $this->actingAs($this->instructor)->post(route('instructor.exercises.timers.reset', [$exercise, $this->student]))
            ->assertSessionHas('success');
        $this->actingAs($this->student)->get(route('student.exercises.show', $exercise))
            ->assertOk()->assertSee('Write code.');
        $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), [
            'source_code' => 'print(1)',
        ])->assertRedirect();
        $this->assertDatabaseCount('code_submissions', 1);
    }
}
