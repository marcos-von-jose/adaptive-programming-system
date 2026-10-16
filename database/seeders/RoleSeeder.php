<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(['name' => 'Administrator'], [
            'description' => 'Has full access to the system.',
        ]);

        Role::updateOrCreate(['name' => 'Instructor'], [
            'description' => 'Manages learning content, assessments, and student progress.',
        ]);

        Role::updateOrCreate(['name' => 'Student'], [
            'description' => 'Uses the system for assessments and programming activities.',
        ]);
    }
}
