<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where('description', 'like', "%{$s}%");
        }

        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->get('model_type'));
        }

        $dateFilter = $request->get('date_filter');
        $from = $request->get('from');
        $to   = $request->get('to');

        match($dateFilter) {
            'today'      => $query->whereDate('created_at', today()),
            'yesterday'  => $query->whereDate('created_at', today()->subDay()),
            'last_week'  => $query->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]),
            'last_month' => $query->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]),
            'custom'     => $query->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
                                  ->when($to,   fn($q) => $q->whereDate('created_at', '<=', $to)),
            default      => null,
        };

        $logs = $query->latest()->paginate(25)->withQueryString();

        $stats = [
            'total'   => ActivityLog::count(),
            'today'   => ActivityLog::whereDate('created_at', today())->count(),
            'actions' => ActivityLog::selectRaw('action, COUNT(*) as total')
                             ->groupBy('action')->orderByDesc('total')->limit(5)->pluck('total', 'action'),
            'models'  => ActivityLog::selectRaw('model_type, COUNT(*) as total')
                             ->whereNotNull('model_type')
                             ->groupBy('model_type')->orderByDesc('total')
                             ->pluck('total', 'model_type'),
        ];

        $actionTypes  = ActivityLog::distinct()->pluck('action')->sort()->values();
        $modelTypes   = ActivityLog::distinct()->whereNotNull('model_type')->pluck('model_type')->sort()->values();

        return view('activity_logs.index', compact('logs', 'stats', 'actionTypes', 'modelTypes'));
    }

    public function clear()
    {
        ActivityLog::where('created_at', '<', now()->subDays(30))->delete();
        return back()->with('success', 'Logs older than 30 days have been cleared.');
    }
}
