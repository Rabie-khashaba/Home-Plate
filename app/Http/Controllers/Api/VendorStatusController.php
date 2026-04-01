<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;

class VendorStatusController extends Controller
{
    public function setPending(int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->status = 'pending';
        $vendor->is_active = false;
        $vendor->save();

        return response()->json([
            'message' => 'Vendor status set to pending.',
            'vendor' => $vendor,
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->status = 'approved';
        $vendor->is_active = true;
        $vendor->save();

        return response()->json([
            'message' => 'Vendor approved.',
            'vendor' => $vendor,
        ]);
    }

    public function reject(int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->status = 'rejected';
        $vendor->is_active = false;
        $vendor->save();

        return response()->json([
            'message' => 'Vendor rejected.',
            'vendor' => $vendor,
        ]);
    }

    public function activate(int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);
        if ($vendor->status !== 'approved') {
            return response()->json([
                'message' => 'Approve vendor before activating.',
            ], 422);
        }

        $vendor->is_active = true;
        $vendor->save();

        return response()->json([
            'message' => 'Vendor activated.',
            'vendor' => $vendor,
        ]);
    }

    public function deactivate(int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->is_active = false;
        $vendor->save();

        return response()->json([
            'message' => 'Vendor deactivated.',
            'vendor' => $vendor,
        ]);
    }
}
