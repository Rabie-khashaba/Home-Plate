<?php

namespace App\Http\Controllers;

use App\Models\PushNotification;
use App\Services\FcmService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = PushNotification::query();
        $dateFilter = $request->get('date_filter');
        $from = $request->get('from');
        $to   = $request->get('to');

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(fn($q) => $q->where('title', 'like', "%{$s}%")->orWhere('body', 'like', "%{$s}%"));
        }

        if ($request->filled('status')) $query->where('status', $request->get('status'));
        if ($request->filled('type'))   $query->where('type',   $request->get('type'));

        match($dateFilter) {
            'today'      => $query->whereDate('created_at', today()),
            'yesterday'  => $query->whereDate('created_at', today()->subDay()),
            'last_week'  => $query->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]),
            'last_month' => $query->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]),
            'custom'     => $query->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
                                  ->when($to,   fn($q) => $q->whereDate('created_at', '<=', $to)),
            default      => null,
        };

        $notifications = $query->latest()->paginate(15)->withQueryString();

        $sq = fn() => PushNotification::when($dateFilter === 'today',      fn($q) => $q->whereDate('created_at', today()))
                                      ->when($dateFilter === 'yesterday',  fn($q) => $q->whereDate('created_at', today()->subDay()))
                                      ->when($dateFilter === 'last_week',  fn($q) => $q->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]))
                                      ->when($dateFilter === 'last_month', fn($q) => $q->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]))
                                      ->when($dateFilter === 'custom' && $from, fn($q) => $q->whereDate('created_at', '>=', $from))
                                      ->when($dateFilter === 'custom' && $to,   fn($q) => $q->whereDate('created_at', '<=', $to));

        $stats = [
            'total'   => $sq()->count(),
            'sent'    => $sq()->where('status', 'sent')->count(),
            'pending' => $sq()->where('status', 'pending')->count(),
            'active'  => $sq()->where('status', 'active')->count(),
        ];

        return view('notifications.index', compact('notifications', 'stats'));
    }

    public function create()
    {
        return view('notifications.create');
    }

    public function store(Request $request, FcmService $fcm)
    {
        $data = $request->validate([
            'title'                   => 'required|string|max:255',
            'body'                    => 'required|string',
            'target_audience'         => 'required|in:all,users,vendors,riders',
            'type'                    => 'required|in:immediate,scheduled,daily,weekly,monthly_day,monthly_date',
            'scheduled_at'            => 'required_if:type,scheduled|nullable|date|after:now',
            'recurrence_time'         => 'required_unless:type,immediate,scheduled|nullable|date_format:H:i',
            'recurrence_day_of_week'  => 'required_if:type,weekly|required_if:type,monthly_day|nullable|integer|min:0|max:6',
            'recurrence_week_of_month'=> 'required_if:type,monthly_day|nullable|integer|min:1|max:4',
            'recurrence_date'         => 'required_if:type,monthly_date|nullable|integer|min:1|max:31',
        ]);

        $data['created_by'] = auth()->id();

        // Set initial status
        $data['status'] = match($data['type']) {
            'immediate' => 'pending',
            'scheduled' => 'pending',
            default     => 'active',   // recurring starts as active
        };

        $notification = PushNotification::create($data);

        // Send immediately if type is immediate
        if ($notification->type === 'immediate') {
            $result = $fcm->sendToAudience(
                $notification->target_audience,
                $notification->title,
                $notification->body,
                $notification->extra_data ?? []
            );

            $notification->update([
                'status'  => $result['success'] ? 'sent' : 'failed',
                'sent_at' => now(),
            ]);

            if ($result['success']) {
                ActivityLogger::log('sent', 'Sent notification: ' . $notification->title, $notification);
            }

            $msg = $result['success']
                ? "Notification sent successfully to {$result['total']} devices."
                : "Notification saved but sending failed. Check FCM configuration.";

            return redirect()->route('notifications.index')->with(
                $result['success'] ? 'success' : 'warning',
                $msg
            );
        }

        ActivityLogger::log('created', 'Scheduled notification: ' . $notification->title, $notification);

        return redirect()->route('notifications.index')
            ->with('success', 'Notification saved successfully.');
    }

    public function destroy(PushNotification $notification)
    {
        ActivityLogger::log('deleted', 'Deleted notification: ' . $notification->title, $notification);
        $notification->delete();
        return back()->with('success', 'Notification deleted.');
    }

    public function sendNow(PushNotification $notification, FcmService $fcm)
    {
        $result = $fcm->sendToAudience(
            $notification->target_audience,
            $notification->title,
            $notification->body,
            $notification->extra_data ?? []
        );

        $notification->update([
            'status'  => $result['success'] ? 'sent' : 'failed',
            'sent_at' => now(),
        ]);
        ActivityLogger::log('sent', 'Manually sent notification: ' . $notification->title, $notification);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? 'Sent successfully.' : 'Failed to send notification.'
        );
    }
}
