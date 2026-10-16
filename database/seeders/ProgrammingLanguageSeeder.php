<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProgrammingLanguage;

class ProgrammingLanguageSeeder extends Seeder
{
    public function run(): void
    {
        $programmingLanguages = [
            ['name' => 'PHP', 'slug' => 'php', 'version' => '8.x', 'status' => 'active'],
            ['name' => 'Python', 'slug' => 'python', 'version' => '3.x', 'status' => 'active'],
            ['name' => 'JavaScript', 'slug' => 'javascript', 'version' => 'ES6+', 'status' => 'active'],
            ['name' => 'Java', 'slug' => 'java', 'version' => '11+', 'status' => 'active'],
            ['name' => 'C#', 'slug' => 'csharp', 'version' => '.NET 5+', 'status' => 'active'],
            ['name' => 'C++', 'slug' => 'cpp', 'version' => '17+', 'status' => 'active'],
            ['name' => 'Ruby', 'slug' => 'ruby', 'version' => '3.x', 'status' => '
active'],
            ['name' => 'Go', 'slug' => 'go', 'version' => '1.18+', 'status' => 'active'],
            ['name' => 'Swift', 'slug' => 'swift', 'version' => '5.x', 'status' => 'active'],
            ['name' => 'Kotlin', 'slug' => 'kotlin', 'version' => '1.5+', 'status' => 'active'],
        ];

        foreach ($programmingLanguages as $language) {
            ProgrammingLanguage::updateOrCreate(
                ['slug' => $language['slug']],
                $language
            );
        }
    }
}
