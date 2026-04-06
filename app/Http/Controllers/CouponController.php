<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query();

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where('code', 'like', "%{$s}%");
        }

        if ($request->filled('status')) {
            match($request->get('status')) {
                'active'    => $query->where('is_active', true)
                                     ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                                     ->where(fn($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit')),
                'inactive'  => $query->where('is_active', false),
                'expired'   => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()),
                default     => null,
            };
        }

        $coupons = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total'    => Coupon::count(),
            'active'   => Coupon::where('is_active', true)
                               ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                               ->where(fn($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
                               ->count(),
            'inactive' => Coupon::where('is_active', false)->count(),
            'expired'  => Coupon::whereNotNull('expires_at')->where('expires_at', '<=', now())->count(),
        ];

        return view('coupons.index', compact('coupons', 'stats'));
    }

    public function create()
    {
        return view('coupons.form', ['coupon' => new Coupon()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code',
            'type'             => 'required|in:percentage,fixed',
            'value'            => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'usage_limit'      => 'nullable|integer|min:1',
            'starts_at'        => 'nullable|date',
            'expires_at'       => 'nullable|date|after_or_equal:starts_at',
            'is_active'        => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $coupon = Coupon::create($data);
        ActivityLogger::log('created', 'Created coupon: ' . $coupon->code, $coupon);

        return redirect()->route('coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon)
    {
        return view('coupons.form', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'type'             => 'required|in:percentage,fixed',
            'value'            => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'usage_limit'      => 'nullable|integer|min:1',
            'starts_at'        => 'nullable|date',
            'expires_at'       => 'nullable|date|after_or_equal:starts_at',
            'is_active'        => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $coupon->update($data);
        ActivityLogger::log('updated', 'Updated coupon: ' . $coupon->code, $coupon);

        return redirect()->route('coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        ActivityLogger::log('deleted', 'Deleted coupon: ' . $coupon->code, $coupon);
        $coupon->delete();
        return back()->with('success', 'Coupon deleted.');
    }

    public function toggle(Coupon $coupon)
    {
        $coupon->update(['is_active' => !$coupon->is_active]);
        return back()->with('success', 'Coupon status updated.');
    }
}
