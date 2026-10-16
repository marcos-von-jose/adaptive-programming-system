<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationAndRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private Role $administrator;

    private Role $instructor;

    private Role $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator = Role::create(['name' => 'Administrator']);
        $this->instructor = Role::create(['name' => 'Instructor']);
        $this->student = Role::create(['name' => 'Student']);
    }

    public function test_registration_always_creates_a_student_account(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'New Learner',
            'email' => 'learner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => $this->administrator->id,
        ]);

        $user = User::where('email', 'learner@example.com')->firstOrFail();

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('Student'));
    }

    public function test_login_redirects_each_user_to_their_own_dashboard(): void
    {
        $users = [
            'Administrator' => route('admin.dashboard'),
            'Instructor' => route('instructor.dashboard'),
            'Student' => route('student.dashboard'),
        ];

        foreach ($users as $roleName => $expectedRoute) {
            $role = Role::where('name', $roleName)->firstOrFail();
            $user = User::factory()->create([
                'role_id' => $role->id,
                'email' => strtolower($roleName).'@example.com',
                'password' => 'password',
            ]);

            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect($expectedRoute);

            $this->post(route('logout'));
        }
    }

    public function test_remember_me_keeps_the_email_after_logout_without_storing_the_password(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->student->id,
            'email' => 'remembered@example.com',
            'password' => 'secret-password',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
            'remember' => '1',
        ])->assertRedirect(route('student.dashboard'))
            ->assertCookie('remembered_email', $user->email);

        $this->withCookie('remembered_email', $user->email)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('value="remembered@example.com"', false)
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertDontSee('value="secret-password"', false);
    }

    public function test_login_without_remember_me_clears_a_previously_remembered_email(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->student->id,
            'password' => 'secret-password',
        ]);

        $this->withCookie('remembered_email', 'previous@example.com')
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'secret-password',
            ])->assertRedirect(route('student.dashboard'))
            ->assertCookieExpired('remembered_email');
    }

    public function test_student_cannot_access_instructor_or_admin_dashboards(): void
    {
        $user = User::factory()->create(['role_id' => $this->student->id]);

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('instructor.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_instructor_cannot_access_student_or_admin_dashboards(): void
    {
        $user = User::factory()->create(['role_id' => $this->instructor->id]);

        $this->actingAs($user)
            ->get(route('instructor.dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_administrator_can_access_only_the_native_admin_dashboard(): void
    {
        $user = User::factory()->create(['role_id' => $this->administrator->id]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('instructor.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertForbidden();
    }

    public function test_filament_routes_are_removed(): void
    {
        $this->get('/admin')->assertNotFound();
        $this->get('/admin/login')->assertNotFound();
    }

    public function test_guest_is_redirected_to_custom_login_from_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_logout_redirects_to_custom_login(): void
    {
        $user = User::factory()->create(['role_id' => $this->administrator->id]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_protected_dashboard(): void
    {
        $this->get(route('student.dashboard'))
            ->assertRedirect(route('login'));
    }
}
