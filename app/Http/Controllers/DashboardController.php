<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $usersCount = AppUser::count();
        $vendorsCount = Vendor::count();
        $deliveriesCount = Delivery::count();
        $ordersCount = Order::count();

        $todayStart = now()->startOfDay();
        $lastWeekStart = now()->subDays(7);

        $ordersDateQuery = static function ($query, $from) {
            $query->where(function ($subQuery) use ($from) {
                $subQuery->whereNotNull('ordered_at')->where('ordered_at', '>=', $from)
                    ->orWhere(function ($fallbackQuery) use ($from) {
                        $fallbackQuery->whereNull('ordered_at')->where('created_at', '>=', $from);
                    });
            });
        };

        $usersLastWeek = AppUser::where('created_at', '>=', $lastWeekStart)->count();
        $vendorsLastWeek = Vendor::where('created_at', '>=', $lastWeekStart)->count();
        $deliveriesLastWeek = Delivery::where('created_at', '>=', $lastWeekStart)->count();
        $ordersLastWeek = Order::where(fn ($query) => $ordersDateQuery($query, $lastWeekStart))->count();
        $ordersToday = Order::where(fn ($query) => $ordersDateQuery($query, $todayStart))->count();
        $revenueToday = Order::where(fn ($query) => $ordersDateQuery($query, $todayStart))->sum('total_amount');
        $pendingOrders = Order::where('status', Order::STATUS_PENDING_VENDOR_PREPARATION)->count();
        $activeOrders = Order::whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])->count();
        $recentOrders = Order::with(['appUser', 'vendor'])
            ->orderByRaw('COALESCE(ordered_at, created_at) desc')
            ->limit(10)
            ->get();

        $topVendors = Vendor::withCount('orders')
            ->orderByDesc('orders_count')
            ->take(3)
            ->get();

        $maxVendorOrders = max(1, (int) ($topVendors->max('orders_count') ?? 1));

        $year = now()->year;
        $ordersByMonth = Order::query()
            ->selectRaw('MONTH(COALESCE(ordered_at, created_at)) as month')
            ->selectRaw('status')
            ->selectRaw('COUNT(*) as total')
            ->whereYear(DB::raw('COALESCE(ordered_at, created_at)'), $year)
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
            ->groupBy('month', 'status')
            ->get();

        $confirmedSeries = array_fill(1, 12, 0);
        $cancelledSeries = array_fill(1, 12, 0);

        foreach ($ordersByMonth as $row) {
            $month = (int) $row->month;
            if ($row->status === Order::STATUS_DELIVERED) {
                $confirmedSeries[$month] = (int) $row->total;
            }
            if ($row->status === Order::STATUS_CANCELLED) {
                $cancelledSeries[$month] = (int) $row->total;
            }
        }

        $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $yAxisMax = 20000;

        return view('dashboard', [
            'usersCount' => $usersCount,
            'vendorsCount' => $vendorsCount,
            'deliveriesCount' => $deliveriesCount,
            'ordersCount' => $ordersCount,
            'usersLastWeek' => $usersLastWeek,
            'vendorsLastWeek' => $vendorsLastWeek,
            'deliveriesLastWeek' => $deliveriesLastWeek,
            'ordersLastWeek' => $ordersLastWeek,
            'ordersToday' => $ordersToday,
            'revenueToday' => $revenueToday,
            'pendingOrders' => $pendingOrders,
            'activeOrders' => $activeOrders,
            'recentOrders' => $recentOrders,
            'topVendors' => $topVendors,
            'maxVendorOrders' => $maxVendorOrders,
            'chartLabels' => $chartLabels,
            'confirmedSeries' => array_values($confirmedSeries),
            'cancelledSeries' => array_values($cancelledSeries),
            'yAxisMax' => $yAxisMax,
        ]);
    }
}
