<?php

namespace App\Http\Controllers;

use App\Models\GeneralSetting;
use Illuminate\Http\Request;

class GeneralSettingController extends Controller
{
    public function edit()
    {
        $setting = GeneralSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'maintenance' => false,
                'message' => 'The app is currently under maintenance. Please try again later.',
            ]
        );

        return view('general_settings.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'maintenance' => 'nullable|boolean',
            'message' => 'nullable|string|max:255',
        ]);

        $data['maintenance'] = (bool) ($data['maintenance'] ?? false);

        $setting = GeneralSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'maintenance' => false,
                'message' => 'The app is currently under maintenance. Please try again later.',
            ]
        );

        $setting->update([
            'maintenance' => $data['maintenance'],
            'message' => $data['message'] ?? $setting->message,
        ]);

        return redirect()
            ->route('general_settings.edit')
            ->with('success', 'Settings saved successfully.');
    }
}
