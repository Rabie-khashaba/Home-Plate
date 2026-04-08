<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function index(Request $request)
    {
        $query = Rating::with(['appUser', 'vendor', 'order']);

        if ($request->filled('search')) {
            $search = $request->get('search');

            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('appUser', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('restaurant_name', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($orderQuery) => $orderQuery->where('order_number', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('vendor_rating')) {
            $query->where('vendor_rating', (int) $request->get('vendor_rating'));
        }

        if ($request->filled('delivery_rating')) {
            $query->where('delivery_rating', (int) $request->get('delivery_rating'));
        }

        $ratings = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => Rating::count(),
            'vendor_avg' => round((float) Rating::whereNotNull('vendor_rating')->avg('vendor_rating'), 1),
            'delivery_avg' => round((float) Rating::whereNotNull('delivery_rating')->avg('delivery_rating'), 1),
            'with_comment' => Rating::whereNotNull('comment')->where('comment', '!=', '')->count(),
        ];

        return view('ratings.index', compact('ratings', 'stats'));
    }

    public function destroy(Rating $rating)
    {
        ActivityLogger::log('deleted', 'Deleted rating #' . $rating->id, $rating);
        $rating->delete();

        return back()->with('success', 'Rating deleted successfully.');
    }
}
