<?php

namespace App\Http\Controllers;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway
    ) {
    }

    public function index(Request $request): View
    {
        $query = Payment::query()->with(['order.appUser', 'order.vendor']);

        if ($request->filled('search')) {
            $s = trim((string) $request->get('search'));
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%{$s}%")
                    ->orWhere('provider_transaction_id', 'like', "%{$s}%")
                    ->orWhere('provider_order_id', 'like', "%{$s}%")
                    ->orWhereHas('order', function ($oq) use ($s) {
                        $oq->where('order_number', 'like', "%{$s}%")
                            ->orWhereHas('appUser', fn($uq) => $uq->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))
                            ->orWhereHas('vendor', fn($vq) => $vq->where('restaurant_name', 'like', "%{$s}%")->orWhere('full_name', 'like', "%{$s}%"));
                    });
            });
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->get('provider'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $dateFilter = $request->get('date_filter');
        $from = $request->get('from');
        $to   = $request->get('to');

        $dateColumn = DB::raw('COALESCE(paid_at, created_at)');

        match($dateFilter) {
            'today'      => $query->whereDate($dateColumn, today()),
            'yesterday'  => $query->whereDate($dateColumn, today()->subDay()),
            'last_week'  => $query->whereBetween($dateColumn, [now()->subWeek()->startOfDay(), now()->endOfDay()]),
            'last_month' => $query->whereBetween($dateColumn, [now()->subMonth()->startOfDay(), now()->endOfDay()]),
            'custom'     => $query->when($from, fn($q) => $q->whereDate($dateColumn, '>=', $from))
                ->when($to, fn($q) => $q->whereDate($dateColumn, '<=', $to)),
            default      => null,
        };

        $payments = $query
            ->latest($dateColumn)
            ->paginate(20)
            ->withQueryString();

        $providers = Payment::query()->distinct()->orderBy('provider')->pluck('provider');
        $statuses = Payment::query()->distinct()->orderBy('status')->pluck('status');

        return view('payments.index', compact('payments', 'providers', 'statuses'));
    }

    public function showPaymentPage(): View
    {
        return view('payments.form');
    }

    public function paymentPageSubmit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'max:10'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150'],
        ]);

        $paymentRequest = new Request([
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'delivery_needed' => false,
            'items' => [],
            'merchant_order_id' => 'WEB-' . now()->format('YmdHis'),
            'shipping_data' => [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone_number' => $validated['phone_number'],
                'email' => $validated['email'],
            ],
        ]);

        $response = $this->paymentGateway->sendPayment($paymentRequest);

        if (($response['success'] ?? false) && ! empty($response['url'])) {
            return redirect()->away($response['url']);
        }

        return back()->withInput()->withErrors([
            'payment' => 'تعذر إنشاء رابط الدفع. راجع إعدادات Paymob أو بيانات الطلب وحاول مرة أخرى.',
        ]);
    }

    public function success(): View
    {
        return view('payments.success');
    }

    public function failed(): View
    {
        return view('payments.failed');
    }
}
