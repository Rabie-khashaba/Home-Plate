<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['appUser', 'vendor']);

        if ($request->filled('search')) {
            $search = $request->get('search');

            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('appUser', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('restaurant_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'resolved' => SupportTicket::where('status', 'resolved')->count(),
        ];

        return view('support.index', compact('tickets', 'stats'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['appUser', 'vendor']);

        return view('support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'admin_reply' => ['required', 'string'],
            'status' => ['nullable', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket->update([
            'admin_reply' => $data['admin_reply'],
            'status' => $data['status'] ?? 'resolved',
            'replied_at' => now(),
        ]);

        ActivityLogger::log('updated', 'Replied to support ticket #' . $ticket->id, $ticket);

        return redirect()->route('support.show', $ticket)->with('success', 'Reply saved successfully.');
    }

    public function destroy(SupportTicket $ticket)
    {
        ActivityLogger::log('deleted', 'Deleted support ticket #' . $ticket->id, $ticket);
        $ticket->delete();

        return redirect()->route('support.index')->with('success', 'Support ticket deleted successfully.');
    }
}
