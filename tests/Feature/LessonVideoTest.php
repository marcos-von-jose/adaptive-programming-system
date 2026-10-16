<?php

namespace Tests\Feature;

use App\Models\LearningContent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_youtube_video_and_students_see_the_embed(): void
    {
        $admin = User::factory()->create(['role_id' => Role::create(['name' => 'Administrator'])->id]);
        $student = User::factory()->create(['role_id' => Role::create(['name' => 'Student'])->id]);

        $this->actingAs($admin)->get(route('admin.content.create'))
            ->assertOk()->assertSee('Upload a lesson video')->assertSee('YouTube video link');

        $this->actingAs($admin)->post(route('admin.content.store'), [
            'title' => 'Loops',
            'body' => 'Learn loops.',
            'status' => 'published',
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ?t=4',
        ])->assertRedirect(route('admin.content.index'));

        $content = LearningContent::firstOrFail();
        $this->assertSame('dQw4w9WgXcQ', $content->youtube_video_id);
        $this->actingAs($student)->get(route('student.learning.index'))
            ->assertOk()->assertSee('Loops')->assertSee('Video lesson');
        $this->actingAs($student)->get(route('student.learning.show', $content))
            ->assertOk()->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');

        $this->actingAs($admin)->post(route('admin.content.store'), [
            'title' => 'Invalid video',
            'body' => 'Text',
            'status' => 'published',
            'youtube_url' => 'https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ',
        ])->assertSessionHasErrors('youtube_url');
        $this->assertSame(1, LearningContent::count());
    }

    public function test_instructor_can_upload_replace_and_remove_a_video_with_access_controls(): void
    {
        Storage::fake('local');
        $instructorRole = Role::create(['name' => 'Instructor']);
        $instructor = User::factory()->create(['role_id' => $instructorRole->id]);
        $otherInstructor = User::factory()->create(['role_id' => $instructorRole->id]);
        $student = User::factory()->create(['role_id' => Role::create(['name' => 'Student'])->id]);
        $admin = User::factory()->create(['role_id' => Role::create(['name' => 'Administrator'])->id]);

        $this->actingAs($instructor)->post(route('instructor.content.store'), [
            'title' => 'Variables',
            'body' => 'Learn variables.',
            'status' => 'published',
            'video_upload' => UploadedFile::fake()->create('variables.mp4', 100, 'video/mp4'),
        ])->assertRedirect(route('instructor.content.index'));

        $content = LearningContent::firstOrFail();
        $oldPath = $content->video_path;
        $this->assertNotNull($oldPath);
        Storage::disk('local')->assertExists($oldPath);
        $this->actingAs($student)->get(route('student.learning.show', $content))
            ->assertOk()->assertSee(route('lessons.video', $content));
        $this->actingAs($student)->get(route('lessons.video', $content))->assertOk()->assertHeader('Content-Type', 'video/mp4');
        $this->actingAs($otherInstructor)->get(route('lessons.video', $content))->assertForbidden();
        $this->actingAs($admin)->get(route('lessons.video', $content))->assertOk();

        $content->update(['status' => 'draft']);
        $this->actingAs($student)->get(route('lessons.video', $content))->assertNotFound();
        $this->actingAs($instructor)->get(route('lessons.video', $content))->assertOk();

        $this->actingAs($instructor)->put(route('instructor.content.update', $content), [
            'title' => 'Variables',
            'body' => 'Learn variables.',
            'status' => 'published',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertRedirect(route('instructor.content.index'));
        $content->refresh();
        $this->assertNull($content->video_path);
        $this->assertSame('dQw4w9WgXcQ', $content->youtube_video_id);
        Storage::disk('local')->assertMissing($oldPath);

        $this->actingAs($instructor)->put(route('instructor.content.update', $content), [
            'title' => 'Variables',
            'body' => 'Learn variables.',
            'status' => 'published',
            'remove_video' => '1',
        ])->assertRedirect(route('instructor.content.index'));
        $this->assertNull($content->fresh()->youtube_video_id);
    }

    public function test_video_upload_validation_rejects_non_video_files_and_two_sources(): void
    {
        Storage::fake('local');
        $instructor = User::factory()->create(['role_id' => Role::create(['name' => 'Instructor'])->id]);
        $lesson = ['title' => 'Arrays', 'body' => 'Learn arrays.', 'status' => 'published'];

        $this->actingAs($instructor)->post(route('instructor.content.store'), $lesson + [
            'video_upload' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('video_upload');

        $this->actingAs($instructor)->post(route('instructor.content.store'), $lesson + [
            'video_upload' => UploadedFile::fake()->create('arrays.mp4', 100, 'video/mp4'),
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])->assertSessionHasErrors('youtube_url');

        $this->assertSame(0, LearningContent::count());
        $this->assertSame([], Storage::disk('local')->files('lesson-videos'));
    }
}
