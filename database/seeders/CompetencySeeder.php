<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Competency;

class CompetencySeeder extends Seeder
{
    public function run(): void
    {
        $competencies = [
            [
                'code' => 'COMP001',
                'name' => 'Variables and Data Types',
                'description' => 'Understanding variables, constants, and basic data types.',
                'sequence' => 1,
                'status' => 'active',
            ],
            [
                'code' => 'COMP002',
                'name' => 'Operators',
                'description' => 'Using arithmetic, comparison, logical, and assignment operators.',
                'sequence' => 2,
                'status' => 'active',
            ],
            [
                'code' => 'COMP003',
                'name' => 'Conditional Statements',
                'description' => 'Applying if, elseif, else, and switch statements.',
                'sequence' => 3,
                'status' => 'active',
            ],
            [
                'code' => 'COMP004',
                'name' => 'Loops',
                'description' => 'Using for, while, do-while, and foreach loops.',
                'sequence' => 4,
                'status' => 'active',
            ],
            [
                'code' => 'COMP005',
                'name' => 'Functions',
                'description' => 'Creating and using user-defined functions.',
                'sequence' => 5,
                'status' => 'active',
            ],
            [
                'code' => 'COMP006',
                'name' => 'Arrays',
                'description' => 'Creating and manipulating indexed and associative arrays.',
                'sequence' => 6,
                'status' => 'active',
            ],
            [
                'code' => 'COMP007',
                'name' => 'Object-Oriented Programming',
                'description' => 'Applying classes, objects, inheritance, and encapsulation.',
                'sequence' => 7,
                'status' => 'active',
            ],
            [
                'code' => 'COMP008',
                'name' => 'Debugging',
                'description' => 'Identifying and correcting syntax, runtime, and logical errors.',
                'sequence' => 8,
                'status' => 'active',
            ],
            [
                'code' => 'COMP009',
                'name' => 'Algorithm Development',
                'description' => 'Designing step-by-step solutions to programming problems.',
                'sequence' => 9,
                'status' => 'active',
            ],
            [
                'code' => 'COMP010',
                'name' => 'Problem Solving',
                'description' => 'Applying programming concepts to solve computational problems.',
                'sequence' => 10,
                'status' => 'active',
            ],
        ];

        foreach ($competencies as $competency) {
            Competency::updateOrCreate(
                ['code' => $competency['code']],
                $competency,
            );
        }
    }
}
