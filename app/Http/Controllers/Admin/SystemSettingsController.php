<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => [
                'site_name' => SystemSetting::valueFor('site_name', 'Adaptive Programming System'),
                'support_email' => SystemSetting::valueFor('support_email', 'support@example.com'),
                'allow_registration' => SystemSetting::valueFor('allow_registration', '1'),
                'default_language' => SystemSetting::valueFor('default_language', 'en'),
                'items_per_page' => SystemSetting::valueFor('items_per_page', '10'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'support_email' => ['required', 'email', 'max:255'],
            'default_language' => ['required', 'in:en'],
            'items_per_page' => ['required', 'integer', 'between:5,100'],
        ]);
        $validated['allow_registration'] = $request->boolean('allow_registration') ? '1' : '0';

        foreach ($validated as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], [
                'value' => (string) $value,
                'type' => in_array($key, ['allow_registration'], true) ? 'boolean' : 'string',
                'group' => 'general',
            ]);
        }

        SystemLog::record('settings.updated', 'Updated general system settings.');

        return back()->with('success', 'System settings saved successfully.');
    }
}
