<?php

namespace Tests\Feature;

use App\Models\Competency;
use App\Models\CompetencyLevel;
use App\Models\ProgrammingLanguage;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_creates_required_roles_when_run_alone(): void
    {
        $this->artisan('db:seed', ['--class' => UserSeeder::class])->assertSuccessful();

        $this->assertSame('Administrator', User::where('email', env('ADMIN_EMAIL', 'admin@example.com'))->firstOrFail()->role->name);
        $this->assertSame('Instructor', User::where('email', env('INSTRUCTOR_EMAIL', 'instructor@example.com'))->firstOrFail()->role->name);
        $this->assertSame(3, Role::count());
    }

    public function test_all_seeders_can_run_again_without_resetting_an_existing_password(): void
    {
        $this->artisan('db:seed')->assertSuccessful();

        $administrator = User::where('email', env('ADMIN_EMAIL', 'admin@example.com'))->firstOrFail();
        $administrator->update(['password' => 'changed-password']);

        $this->artisan('db:seed')->assertSuccessful();

        $this->assertSame(3, Role::count());
        $this->assertSame(2, User::count());
        $this->assertSame(10, ProgrammingLanguage::count());
        $this->assertSame(4, CompetencyLevel::count());
        $this->assertSame(10, Competency::count());
        $this->assertTrue(Hash::check('changed-password', $administrator->fresh()->password));
    }
}
