<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\CodeSubmission;
use App\Models\Exercise;
use App\Models\LearningContent;
use App\Models\Role;
use App\Models\StudentActivitySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningPrerequisitesTest extends TestCase
{
    use RefreshDatabase;

    public function test_activities_are_locked_when_no_lessons_are_published(): void
    {
        $student = User::factory()->create(['role_id' => Role::create(['name' => 'Student'])->id]);
        $assessment = Assessment::create(['title' => 'Quiz', 'status' => 'published', 'passing_score' => 70, 'time_limit_minutes' => 30]);
        $exercise = Exercise::create(['title' => 'Code', 'prompt' => 'Write code.', 'status' => 'published', 'time_limit_minutes' => 30]);

        $this->actingAs($student)->get(route('student.assessments.index'))->assertOk()->assertSee('Locked');
        $this->actingAs($student)->get(route('student.exercises.index'))->assertOk()->assertSee('Locked');
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))->assertRedirect(route('student.learning.index'));
        $this->actingAs($student)->get(route('student.exercises.show', $exercise))->assertRedirect(route('student.learning.index'));
        $this->actingAs($student)->get(route('student.assessments.work', $assessment))->assertRedirect(route('student.learning.index'));
        $this->actingAs($student)->get(route('student.exercises.work', $exercise))->assertRedirect(route('student.learning.index'));
        $this->actingAs($student)->postJson(route('student.assessments.timer.start', $assessment))->assertForbidden();
        $this->actingAs($student)->postJson(route('student.exercises.timer.start', $exercise))->assertForbidden();
        $this->actingAs($student)->post(route('student.assessments.store', $assessment), ['answers' => []])->assertRedirect(route('student.learning.index'));
        $this->actingAs($student)->post(route('student.exercises.submit', $exercise), ['source_code' => 'print(1)'])->assertRedirect(route('student.learning.index'));

        $this->assertSame(0, StudentActivitySession::count());
        $this->assertSame(0, AssessmentAttempt::count());
        $this->assertSame(0, CodeSubmission::count());
    }

    public function test_all_published_lessons_must_be_completed_by_each_student(): void
    {
        $studentRole = Role::create(['name' => 'Student']);
        $student = User::factory()->create(['role_id' => $studentRole->id]);
        $otherStudent = User::factory()->create(['role_id' => $studentRole->id]);
        $assessment = Assessment::create(['title' => 'Quiz', 'status' => 'published', 'passing_score' => 70]);
        $item = $assessment->items()->create(['prompt' => 'Choose one', 'position' => 1]);
        $choice = $item->choices()->create(['text' => 'Correct', 'is_correct' => true]);
        $exercise = Exercise::create(['title' => 'Code', 'prompt' => 'Write code.', 'status' => 'published']);
        $first = LearningContent::create(['title' => 'First lesson', 'slug' => 'first-lesson', 'body' => 'Read first.', 'status' => 'published']);
        $second = LearningContent::create(['title' => 'Second lesson', 'slug' => 'second-lesson', 'body' => 'Read second.', 'status' => 'published']);
        $draft = LearningContent::create(['title' => 'Future lesson', 'slug' => 'future-lesson', 'body' => 'Read later.', 'status' => 'draft']);

        $this->actingAs($student)->post(route('student.learning.complete', $first))->assertSessionHas('success');
        $this->actingAs($student)->get(route('student.assessments.index'))->assertOk()->assertSee('1 lesson remains');
        $this->actingAs($student)->get(route('student.exercises.show', $exercise))->assertRedirect(route('student.learning.index'));

        $this->actingAs($student)->post(route('student.learning.complete', $second))->assertSessionHas('success');
        $this->actingAs($student)->get(route('student.assessments.index'))->assertOk()->assertSee('Start');
        $this->actingAs($student)->get(route('student.exercises.index'))->assertOk()->assertSee('Solve');
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))->assertOk();
        $this->actingAs($student)->get(route('student.exercises.show', $exercise))->assertOk();

        $this->actingAs($student)->post(route('student.assessments.store', $assessment), [
            'answers' => [$item->id => $choice->id],
        ])->assertRedirect(route('student.assessments.index'));
        $this->actingAs($student)->post(route('student.exercises.submit', $exercise), [
            'source_code' => 'print(1)',
        ])->assertRedirect();
        $submission = CodeSubmission::firstOrFail();

        $this->actingAs($otherStudent)->get(route('student.assessments.show', $assessment))->assertRedirect(route('student.learning.index'));
        $this->actingAs($otherStudent)->get(route('student.exercises.show', $exercise))->assertRedirect(route('student.learning.index'));

        $draft->update(['status' => 'published']);
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))->assertOk()->assertSee('Your result');
        $this->actingAs($student)->get(route('student.submissions.show', $submission))->assertOk();
        $this->actingAs($student)->get(route('student.exercises.show', $exercise))->assertRedirect(route('student.learning.index'));
    }
}
