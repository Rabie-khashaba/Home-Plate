<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;

class AppUserStatusController extends Controller
{
    public function activate(int $id): JsonResponse
    {
        $app_user = AppUser::findOrFail($id);
        $app_user->is_active = true;
        $app_user->save();

        return response()->json([
            'message' => 'App user activated.',
            'user' => $app_user,
        ]);
    }

    public function deactivate(int $id): JsonResponse
    {
        $app_user = AppUser::findOrFail($id);
        $app_user->is_active = false;
        $app_user->save();

        return response()->json([
            'message' => 'App user deactivated.',
            'user' => $app_user,
        ]);
    }
}
