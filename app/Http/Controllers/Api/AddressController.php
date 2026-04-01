<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\AppUser;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function storeForAppUser(Request $request, int $id): JsonResponse
    {
        $appUser = $this->requireActor($request->user(), AppUser::class, 'Only app users can add addresses.');
        if ($appUser instanceof JsonResponse) {
            return $appUser;
        }

        if ((int) $appUser->id !== $id) {
            return response()->json(['message' => 'You can only add addresses for your own profile.'], 403);
        }

        $data = $this->validateAddress($request);
        $address = $appUser->addresses()->create($data);

        return response()->json([
            'message' => 'Address created successfully.',
            'data' => $address,
        ], 201);
    }

    public function storeForVendor(Request $request, int $id): JsonResponse
    {
        $vendor = $this->requireActor($request->user(), Vendor::class, 'Only vendors can add addresses.');
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        if ((int) $vendor->id !== $id) {
            return response()->json(['message' => 'You can only add addresses for your own profile.'], 403);
        }

        $data = $this->validateAddress($request);
        $address = $vendor->addresses()->create($data);

        return response()->json([
            'message' => 'Address created successfully.',
            'data' => $address,
        ], 201);
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:100',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'town_city' => 'required|string|max:150',
            'region_state' => 'required|string|max:150',
        ]);
    }

    private function requireActor(?Model $actor, string $expectedClass, string $message): Model|JsonResponse
    {
        if (! $actor instanceof $expectedClass) {
            return response()->json(['message' => $message], 403);
        }

        return $actor;
    }
}
