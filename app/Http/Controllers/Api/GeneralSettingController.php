<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;

class GeneralSettingController extends Controller
{
    public function show()
    {
        $setting = GeneralSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'maintenance' => false,
                'message' => 'The app is currently under maintenance. Please try again later.',
            ]
        );

        return response()->json([
            'message' => 'General setting fetched successfully.',
            'data' => $setting,
        ]);
    }
}
