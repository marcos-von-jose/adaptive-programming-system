<?php

namespace Tests\Feature;

use App\Models\LearningContent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonDeletionProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_instructor_deletions_update_student_progress_counts_and_lists(): void
    {
        $admin = User::factory()->create(['role_id' => Role::create(['name' => 'Administrator'])->id]);
        $instructor = User::factory()->create(['role_id' => Role::create(['name' => 'Instructor'])->id]);
        $student = User::factory()->create(['role_id' => Role::create(['name' => 'Student'])->id]);
        $first = LearningContent::create([
            'title' => 'First lesson', 'slug' => 'first-lesson', 'body' => 'First.',
            'status' => 'published', 'created_by' => $instructor->id,
        ]);
        $second = LearningContent::create([
            'title' => 'Second lesson', 'slug' => 'second-lesson', 'body' => 'Second.',
            'status' => 'published', 'created_by' => $instructor->id,
        ]);

        foreach ([$first, $second] as $lesson) {
            $student->learningProgress()->create([
                'learning_content_id' => $lesson->id,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        $this->actingAs($student)->get(route('student.progress.index'))
            ->assertOk()->assertSee('aria-valuenow="2"', false)->assertSee('aria-valuemax="2"', false)
            ->assertViewHas('publishedLessons', 2)->assertSee('First lesson')->assertSee('Second lesson');

        $this->actingAs($admin)->delete(route('admin.content.destroy', $first))->assertSessionHas('success');
        $this->assertDatabaseMissing('learning_progress', ['learning_content_id' => $first->id]);
        $this->actingAs($student)->get(route('student.progress.index'))
            ->assertOk()->assertSee('aria-valuenow="1"', false)->assertSee('aria-valuemax="1"', false)
            ->assertViewHas('publishedLessons', 1)->assertSee('Second lesson')
            ->assertDontSee('First lesson')->assertDontSee('Removed lesson')
            ->assertViewHas('progress', fn ($progress) => $progress->count() === 1);
        $this->actingAs($admin)->get(route('admin.progress.index'))
            ->assertViewHas('students', fn ($students) => $students->first()->completed_lessons_count === 1);
        $this->actingAs($admin)->get(route('admin.reports.index'))
            ->assertViewHas('completedLessons', 1);

        $this->actingAs($instructor)->delete(route('instructor.content.destroy', $second))->assertSessionHas('success');
        $this->assertDatabaseCount('learning_progress', 0);
        $this->actingAs($student)->get(route('student.progress.index'))
            ->assertOk()->assertSee('aria-valuenow="0"', false)->assertViewHas('publishedLessons', 0)
            ->assertSee('No lessons completed yet.')
            ->assertDontSee('Removed lesson')
            ->assertViewHas('progress', fn ($progress) => $progress->isEmpty());
    }
}
