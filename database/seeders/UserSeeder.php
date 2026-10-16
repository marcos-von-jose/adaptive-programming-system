<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! Role::where('name', 'Administrator')->exists()
            || ! Role::where('name', 'Instructor')->exists()) {
            $this->call(RoleSeeder::class);
        }

        $administrator = Role::where('name', 'Administrator')->firstOrFail();
        $instructor = Role::where('name', 'Instructor')->firstOrFail();

        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'role_id' => $administrator->id,
                'name' => 'System Administrator',
                'password' => env('ADMIN_PASSWORD', 'password'),
            ],
        );

        User::firstOrCreate(
            ['email' => env('INSTRUCTOR_EMAIL', 'instructor@example.com')],
            [
                'role_id' => $instructor->id,
                'name' => 'Sample Instructor',
                'password' => env('INSTRUCTOR_PASSWORD', 'password'),
            ],
        );
    }
}
