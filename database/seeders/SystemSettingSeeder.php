<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'site_name' => ['value' => 'Adaptive Programming System', 'type' => 'string'],
            'support_email' => ['value' => 'support@example.com', 'type' => 'string'],
            'allow_registration' => ['value' => '1', 'type' => 'boolean'],
            'default_language' => ['value' => 'en', 'type' => 'string'],
            'items_per_page' => ['value' => '10', 'type' => 'integer'],
        ];

        foreach ($settings as $key => $setting) {
            SystemSetting::firstOrCreate(['key' => $key], [
                ...$setting,
                'group' => 'general',
            ]);
        }
    }
}
