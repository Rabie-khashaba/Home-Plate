<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use App\Models\WalletTransaction;
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
            'paid'            => $baseQ()->whereIn('payment_status', ['paid', 'payment_confirmed'])->count(),
        ];

        $paymentMethods = Order::distinct()->whereNotNull('payment_method')->pluck('payment_method');

        $walletTransactionsQuery = WalletTransaction::with(['wallet.owner', 'order', 'createdBy']);
        if ($request->filled('search')) {
            $s = $request->get('search');
            $walletTransactionsQuery->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                    ->orWhereHas('order', fn($oq) => $oq->where('order_number', 'like', "%{$s}%"))
                    ->orWhereHas('wallet.owner', function ($ownerQuery) use ($s) {
                        $ownerQuery->where('restaurant_name', 'like', "%{$s}%")
                            ->orWhere('full_name', 'like', "%{$s}%")
                            ->orWhere('first_name', 'like', "%{$s}%")
                            ->orWhere('name', 'like', "%{$s}%");
                    });
            });
        }

        match($dateFilter) {
            'today'      => $walletTransactionsQuery->whereDate('created_at', today()),
            'yesterday'  => $walletTransactionsQuery->whereDate('created_at', today()->subDay()),
            'last_week'  => $walletTransactionsQuery->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]),
            'last_month' => $walletTransactionsQuery->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]),
            'custom'     => $walletTransactionsQuery->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to)),
            default      => null,
        };

        $walletTransactions = $walletTransactionsQuery
            ->latest()
            ->paginate(20, ['*'], 'wallet_page')
            ->withQueryString();

        $paymentsQuery = Payment::query()->with(['order.appUser', 'order.vendor']);
        if ($request->filled('search')) {
            $s = $request->get('search');
            $paymentsQuery->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%{$s}%")
                    ->orWhere('provider_transaction_id', 'like', "%{$s}%")
                    ->orWhere('provider_order_id', 'like', "%{$s}%")
                    ->orWhereHas('order', fn($oq) => $oq->where('order_number', 'like', "%{$s}%"));
            });
        }

        match($dateFilter) {
            'today'      => $paymentsQuery->whereDate(DB::raw('COALESCE(paid_at, created_at)'), today()),
            'yesterday'  => $paymentsQuery->whereDate(DB::raw('COALESCE(paid_at, created_at)'), today()->subDay()),
            'last_week'  => $paymentsQuery->whereBetween(DB::raw('COALESCE(paid_at, created_at)'), [now()->subWeek()->startOfDay(), now()->endOfDay()]),
            'last_month' => $paymentsQuery->whereBetween(DB::raw('COALESCE(paid_at, created_at)'), [now()->subMonth()->startOfDay(), now()->endOfDay()]),
            'custom'     => $paymentsQuery->when($from, fn($q) => $q->whereDate(DB::raw('COALESCE(paid_at, created_at)'), '>=', $from))
                ->when($to, fn($q) => $q->whereDate(DB::raw('COALESCE(paid_at, created_at)'), '<=', $to)),
            default      => null,
        };

        $payments = $paymentsQuery
            ->latest(DB::raw('COALESCE(paid_at, created_at)'))
            ->paginate(20, ['*'], 'payment_page')
            ->withQueryString();

        return view('transactions.index', compact('transactions', 'stats', 'paymentMethods', 'walletTransactions', 'payments'));
    }
}
