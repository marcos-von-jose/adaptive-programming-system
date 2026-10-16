<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CompetencyLevel;

class CompetencyLevelSeeder extends Seeder
{
    public function run(): void
    {
        CompetencyLevel::updateOrCreate(['name' => 'Beginner'], [
            'min_score' => 0,
            'max_score' => 59,
            'rank' => 1,
            'description' => 'Requires foundational programming practice.',
        ]);

        CompetencyLevel::updateOrCreate(['name' => 'Developing'], [
            'min_score' => 60,
            'max_score' => 74,
            'rank' => 2,
            'description' => 'Shows developing understanding of programming concepts.',
        ]);

        CompetencyLevel::updateOrCreate(['name' => 'Proficient'], [
            'min_score' => 75,
            'max_score' => 89,
            'rank' => 3,
            'description' => 'Demonstrates good understanding and application of programming concepts.',
        ]);

        CompetencyLevel::updateOrCreate(['name' => 'Advanced'], [
            'min_score' => 90,
            'max_score' => 100,
            'rank' => 4,
            'description' => 'Demonstrates advanced programming competency.',
        ]);
    }
}
