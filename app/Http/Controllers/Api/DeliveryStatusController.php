<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;

class DeliveryStatusController extends Controller
{
    public function setPending(int $id): JsonResponse
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->status = 'pending';
        $delivery->is_active = false;
        $delivery->save();

        return response()->json([
            'message' => 'Delivery status set to pending.',
            'delivery' => $delivery,
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->status = 'approved';
        $delivery->is_active = true;
        $delivery->save();

        return response()->json([
            'message' => 'Delivery approved.',
            'delivery' => $delivery,
        ]);
    }

    public function reject(int $id): JsonResponse
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->status = 'rejected';
        $delivery->is_active = false;
        $delivery->save();

        return response()->json([
            'message' => 'Delivery rejected.',
            'delivery' => $delivery,
        ]);
    }

    public function activate(int $id): JsonResponse
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->is_active = true;
        $delivery->status = 'approved';
        $delivery->save();

        return response()->json([
            'message' => 'Delivery activated.',
            'delivery' => $delivery,
        ]);
    }

    public function deactivate(int $id): JsonResponse
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->is_active = false;
        $delivery->save();

        return response()->json([
            'message' => 'Delivery deactivated.',
            'delivery' => $delivery,
        ]);
    }
}
