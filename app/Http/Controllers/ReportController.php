<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Delivery;
use App\Models\Item;
use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $year = now()->year;

        // Revenue by month (delivered orders only)
        $revenueByMonth = Order::selectRaw('MONTH(COALESCE(ordered_at, created_at)) as month, SUM(total_amount) as revenue, COUNT(*) as orders')
            ->whereYear(DB::raw('COALESCE(ordered_at, created_at)'), $year)
            ->where('status', Order::STATUS_DELIVERED)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $revenueData = [];
        $ordersData  = [];
        for ($m = 1; $m <= 12; $m++) {
            $revenueData[] = (float) ($revenueByMonth[$m]->revenue ?? 0);
            $ordersData[]  = (int)   ($revenueByMonth[$m]->orders  ?? 0);
        }

        // New users/vendors by month
        $usersByMonth = AppUser::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)->groupBy('month')->pluck('total', 'month');
        $vendorsByMonth = Vendor::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)->groupBy('month')->pluck('total', 'month');

        $usersData   = array_map(fn($m) => (int)($usersByMonth[$m]  ?? 0), range(1, 12));
        $vendorsData = array_map(fn($m) => (int)($vendorsByMonth[$m] ?? 0), range(1, 12));

        // Top 10 items by order frequency
        $topItems = Item::withCount('orderItems')
            ->having('order_items_count', '>', 0)
            ->orderByDesc('order_items_count')
            ->take(10)
            ->get();

        // Top 10 vendors by orders
        $topVendors = Vendor::withCount('orders')
            ->having('orders_count', '>', 0)
            ->orderByDesc('orders_count')
            ->take(10)
            ->get();

        // Order status breakdown
        $statusBreakdown = Order::selectRaw('status, COUNT(*) as total')
            ->whereYear(DB::raw('COALESCE(ordered_at, created_at)'), $year)
            ->groupBy('status')
            ->pluck('total', 'status');

        // Summary KPIs
        $kpis = [
            'total_revenue'   => Order::where('status', Order::STATUS_DELIVERED)->sum('total_amount'),
            'total_orders'    => Order::count(),
            'delivered'       => Order::where('status', Order::STATUS_DELIVERED)->count(),
            'cancelled'       => Order::where('status', Order::STATUS_CANCELLED)->count(),
            'total_users'     => AppUser::count(),
            'total_vendors'   => Vendor::where('status', 'approved')->count(),
            'total_riders'    => Delivery::where('status', 'approved')->count(),
            'avg_order_value' => Order::where('status', Order::STATUS_DELIVERED)->avg('total_amount'),
        ];

        return view('reports.index', compact(
            'months', 'revenueData', 'ordersData',
            'usersData', 'vendorsData',
            'topItems', 'topVendors',
            'statusBreakdown', 'kpis', 'year'
        ));
    }
}
