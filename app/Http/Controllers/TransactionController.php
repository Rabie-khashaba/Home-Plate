<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['appUser', 'vendor'])
            ->whereNotNull('payment_method');

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(fn($q) =>
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhereHas('appUser', fn($q2) => $q2->where('name', 'like', "%{$s}%"))
                  ->orWhere('payment_reference', 'like', "%{$s}%")
            );
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->get('payment_method'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        $dateFilter = $request->get('date_filter');
        $from = $request->get('from');
        $to   = $request->get('to');

        match($dateFilter) {
            'today'      => $query->whereDate(DB::raw('COALESCE(ordered_at, created_at)'), today()),
            'yesterday'  => $query->whereDate(DB::raw('COALESCE(ordered_at, created_at)'), today()->subDay()),
            'last_week'  => $query->whereBetween(DB::raw('COALESCE(ordered_at, created_at)'), [now()->subWeek()->startOfDay(), now()->endOfDay()]),
            'last_month' => $query->whereBetween(DB::raw('COALESCE(ordered_at, created_at)'), [now()->subMonth()->startOfDay(), now()->endOfDay()]),
            'custom'     => $query->when($from, fn($q) => $q->whereDate(DB::raw('COALESCE(ordered_at, created_at)'), '>=', $from))
                                  ->when($to,   fn($q) => $q->whereDate(DB::raw('COALESCE(ordered_at, created_at)'), '<=', $to)),
            default      => null,
        };

        $transactions = $query->latest(DB::raw('COALESCE(ordered_at, created_at)'))->paginate(20)->withQueryString();

        // Stats
        $baseQ = fn() => Order::when($dateFilter === 'today',      fn($q) => $q->whereDate(DB::raw('COALESCE(ordered_at, created_at)'), today()))
                               ->when($dateFilter === 'yesterday',  fn($q) => $q->whereDate(DB::raw('COALESCE(ordered_at, created_at)'), today()->subDay()))
                               ->when($dateFilter === 'last_week',  fn($q) => $q->whereBetween(DB::raw('COALESCE(ordered_at, created_at)'), [now()->subWeek()->startOfDay(), now()->endOfDay()]))
                               ->when($dateFilter === 'last_month', fn($q) => $q->whereBetween(DB::raw('COALESCE(ordered_at, created_at)'), [now()->subMonth()->startOfDay(), now()->endOfDay()]))
                               ->when($dateFilter === 'custom' && $from, fn($q) => $q->whereDate(DB::raw('COALESCE(ordered_at, created_at)'), '>=', $from))
                               ->when($dateFilter === 'custom' && $to,   fn($q) => $q->whereDate(DB::raw('COALESCE(ordered_at, created_at)'), '<=', $to));

        $stats = [
            'total_revenue'   => $baseQ()->where('status', Order::STATUS_DELIVERED)->sum('total_amount'),
            'total_fees'      => $baseQ()->where('status', Order::STATUS_DELIVERED)->sum('delivery_fee'),
            'count'           => $baseQ()->count(),
            'paid'            => $baseQ()->where('payment_status', 'paid')->count(),
        ];

        $paymentMethods = Order::distinct()->whereNotNull('payment_method')->pluck('payment_method');

        return view('transactions.index', compact('transactions', 'stats', 'paymentMethods'));
    }
}
