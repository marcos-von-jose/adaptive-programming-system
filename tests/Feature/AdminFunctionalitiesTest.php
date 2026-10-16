<?php

namespace Tests\Feature;

use App\Models\Competency;
use App\Models\CodeSubmission;
use App\Models\Exercise;
use App\Models\LearningContent;
use App\Models\ProgrammingLanguage;
use App\Models\Role;
use App\Models\SystemLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminFunctionalitiesTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    private Role $studentRole;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Administrator']);
        Role::create(['name' => 'Instructor']);
        $this->studentRole = Role::create(['name' => 'Student']);
        $this->administrator = User::factory()->create(['role_id' => $adminRole->id]);
    }

    public function test_administrator_can_open_every_management_module(): void
    {
        $this->actingAs($this->administrator);

        $this->get(route('admin.users.index'))->assertOk()->assertSee('Manage users');
        $this->get(route('admin.settings.edit'))->assertOk()->assertSee('System settings');
        $this->get(route('admin.content.index'))->assertOk()->assertSee('Manage content');
        $this->get(route('admin.logs.index'))->assertOk()->assertSee('System logs');
        $this->get(route('admin.reports.index'))->assertOk()->assertSee('System reports');
    }

    public function test_student_cannot_open_admin_management_modules(): void
    {
        $student = User::factory()->create(['role_id' => $this->studentRole->id]);

        foreach (['admin.users.index', 'admin.settings.edit', 'admin.content.index', 'admin.logs.index', 'admin.reports.index'] as $route) {
            $this->actingAs($student)->get(route($route))->assertForbidden();
        }
    }

    public function test_administrator_can_create_update_and_delete_a_user(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('admin.users.store'), [
                'name' => 'Managed Student',
                'email' => 'managed@example.com',
                'role_id' => $this->studentRole->id,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'managed@example.com')->firstOrFail();

        $this->actingAs($this->administrator)
            ->put(route('admin.users.update', $user), [
                'name' => 'Updated Student',
                'email' => 'managed@example.com',
                'role_id' => $this->studentRole->id,
                'password' => '',
                'password_confirmation' => '',
            ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Student']);

        $this->actingAs($this->administrator)
            ->delete(route('admin.users.destroy', $user))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_administrator_can_upload_and_replace_a_user_photo(): void
    {
        Storage::fake('local');

        $this->actingAs($this->administrator)->get(route('admin.users.create'))
            ->assertOk()->assertSee('enctype="multipart/form-data"', false);
        $this->actingAs($this->administrator)->post(route('admin.users.store'), [
            'name' => 'Photo Student',
            'email' => 'photo@example.com',
            'role_id' => $this->studentRole->id,
            'password' => 'password',
            'password_confirmation' => 'password',
            'profile_photo' => UploadedFile::fake()->image('first.png'),
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'photo@example.com')->firstOrFail();
        $firstPath = $user->profile_photo_path;
        Storage::disk('local')->assertExists($firstPath);
        $this->actingAs($this->administrator)->get(route('admin.users.index'))
            ->assertOk()->assertSee('<th>Photo</th>', false)
            ->assertSee('alt="Profile photo of Photo Student"', false);
        $this->actingAs($this->administrator)->get(route('users.photo', $user))->assertOk();
        $this->actingAs($this->administrator)->get(route('admin.users.edit', $user))
            ->assertOk()->assertSee(route('users.photo', $user));

        $this->actingAs($this->administrator)->put(route('admin.users.update', $user), [
            'name' => 'Photo Student',
            'email' => 'photo@example.com',
            'role_id' => $this->studentRole->id,
            'password' => '',
            'password_confirmation' => '',
            'profile_photo' => UploadedFile::fake()->image('replacement.jpg'),
        ])->assertRedirect(route('admin.users.index'));

        $user->refresh();
        Storage::disk('local')->assertExists($user->profile_photo_path);
        Storage::disk('local')->assertMissing($firstPath);

        $this->actingAs($this->administrator)->put(route('admin.users.update', $user), [
            'name' => 'Photo Student',
            'email' => 'photo@example.com',
            'role_id' => $this->studentRole->id,
            'profile_photo' => UploadedFile::fake()->create('document.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('profile_photo');
        Storage::disk('local')->assertExists($user->profile_photo_path);

        $lastPath = $user->profile_photo_path;
        $this->actingAs($this->administrator)->delete(route('admin.users.destroy', $user))
            ->assertSessionHas('success');
        Storage::disk('local')->assertMissing($lastPath);
    }

    public function test_manage_users_modal_forms_can_create_and_edit_with_json_validation(): void
    {
        Storage::fake('local');
        $this->actingAs($this->administrator)->get(route('admin.users.index'))
            ->assertOk()->assertSee('data-user-modal="user-create-template"', false)
            ->assertSee('id="user-create-template"', false)
            ->assertSee('enctype="multipart/form-data"', false);

        $this->actingAs($this->administrator)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.users.store'), [
            'name' => 'Modal Student',
            'email' => 'modal@example.com',
            'role_id' => $this->studentRole->id,
            'password' => 'password',
            'password_confirmation' => 'password',
            'profile_photo' => UploadedFile::fake()->image('modal.png'),
        ])->assertOk()->assertJson(['message' => 'User created successfully.']);

        $user = User::where('email', 'modal@example.com')->firstOrFail();
        Storage::disk('local')->assertExists($user->profile_photo_path);
        $this->actingAs($this->administrator)->get(route('admin.users.index'))
            ->assertOk()->assertSee('data-user-modal="user-edit-template-'.$user->id.'"', false)
            ->assertSee('data-confirm-delete-user', false);

        $this->actingAs($this->administrator)->putJson(route('admin.users.update', $user), [
            'name' => 'Modal Student',
            'email' => 'invalid-email',
            'role_id' => $this->studentRole->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->actingAs($this->administrator)->putJson(route('admin.users.update', $user), [
            'name' => 'Updated Modal Student',
            'email' => 'modal@example.com',
            'role_id' => $this->studentRole->id,
        ])->assertOk()->assertJson(['message' => 'User updated successfully.']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Modal Student']);
    }

    public function test_settings_can_disable_public_registration(): void
    {
        $this->actingAs($this->administrator)
            ->put(route('admin.settings.update'), [
                'site_name' => 'Adaptive Programming System',
                'support_email' => 'support@example.com',
                'default_language' => 'en',
                'items_per_page' => 20,
            ])->assertSessionHas('success');

        $this->assertSame('0', SystemSetting::valueFor('allow_registration'));

        $this->post(route('logout'));
        $this->get(route('register'))->assertNotFound();
    }

    public function test_administrator_can_manage_content_and_actions_are_logged(): void
    {
        $competency = Competency::create([
            'code' => 'TEST01', 'name' => 'Testing', 'sequence' => 1, 'status' => 'active',
        ]);
        $language = ProgrammingLanguage::create([
            'name' => 'PHP', 'slug' => 'php', 'version' => '8.x', 'status' => 'active',
        ]);

        $this->actingAs($this->administrator)
            ->post(route('admin.content.store'), [
                'title' => 'Variables in PHP',
                'summary' => 'A short introduction.',
                'body' => 'Complete lesson content.',
                'competency_id' => $competency->id,
                'programming_language_id' => $language->id,
                'status' => 'draft',
            ])->assertRedirect(route('admin.content.index'));

        $content = LearningContent::where('title', 'Variables in PHP')->firstOrFail();

        $this->actingAs($this->administrator)
            ->put(route('admin.content.update', $content), [
                'title' => 'Variables and Types in PHP',
                'summary' => 'An updated introduction.',
                'body' => 'Updated lesson content.',
                'competency_id' => $competency->id,
                'programming_language_id' => $language->id,
                'status' => 'published',
            ])->assertRedirect(route('admin.content.index'));

        $this->assertDatabaseHas('learning_contents', ['id' => $content->id, 'status' => 'published']);
        $this->assertDatabaseHas('system_logs', ['action' => 'content.created']);
        $this->assertDatabaseHas('system_logs', ['action' => 'content.updated']);
    }

    public function test_administrator_can_print_analytics(): void
    {
        $student = User::factory()->create(['role_id' => $this->studentRole->id]);
        $exercise = Exercise::create(['title' => 'Review summary', 'prompt' => 'Solve it.', 'status' => 'published']);
        foreach (['submitted', 'reviewed', 'correct', 'needs_retry'] as $status) {
            CodeSubmission::create([
                'exercise_id' => $exercise->id, 'student_id' => $student->id,
                'source_code' => 'answer', 'status' => $status, 'submitted_at' => now(),
            ]);
        }

        $this->actingAs($this->administrator)->get(route('admin.reports.index'))
            ->assertOk()->assertSee('Print report')
            ->assertSee('onclick="window.print()"', false)
            ->assertDontSee('Download CSV report')
            ->assertViewHas('reviewedSubmissions', 3)
            ->assertViewHas('reviewRate', 75);
        $this->actingAs($this->administrator)->get('/admin/reports/download')->assertNotFound();
    }

    public function test_administrator_can_clear_all_system_logs(): void
    {
        SystemLog::create([
            'user_id' => $this->administrator->id,
            'action' => 'content.updated',
            'description' => 'Changed a lesson.',
        ]);
        SystemLog::create([
            'user_id' => $this->administrator->id,
            'action' => 'content.created',
            'description' => 'Created a lesson.',
        ]);

        $this->actingAs($this->administrator)
            ->get(route('admin.logs.index'))
            ->assertOk()
            ->assertSee(route('admin.logs.clear'))
            ->assertSee('Clear all logs')
            ->assertSee('data-log-count="2"', false);

        $this->delete(route('admin.logs.clear'))
            ->assertRedirect(route('admin.logs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('system_logs', 0);
        $this->get(route('admin.logs.index'))
            ->assertOk()
            ->assertSee('No system activity has been recorded yet.')
            ->assertSee('disabled', false);
    }

    public function test_student_cannot_clear_system_logs(): void
    {
        $student = User::factory()->create(['role_id' => $this->studentRole->id]);
        $log = SystemLog::create(['action' => 'content.created', 'description' => 'Created a lesson.']);

        $this->actingAs($student)
            ->delete(route('admin.logs.clear'))
            ->assertForbidden();

        $this->assertDatabaseHas('system_logs', ['id' => $log->id]);
    }
}
