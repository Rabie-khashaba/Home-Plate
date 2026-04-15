<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Order;
use App\Services\OrderPaymentRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymobWebhookController extends Controller
{
    public function handle(Request $request, PaymentGatewayInterface $paymentGateway, OrderPaymentRecorder $paymentRecorder): JsonResponse
    {
        $result = $paymentGateway->callBack($request);
        $orderId = $result['order_id'] ?? null;

        Storage::put(
            'paymob/webhook-' . now()->format('YmdHis') . '.json',
            json_encode(['result' => $result, 'headers' => $request->headers->all(), 'body' => $request->all()])
        );

        if (! $orderId) {
            return response()->json(['message' => 'Missing merchant order id.'], 400);
        }

        /** @var \App\Models\Order|null $order */
        $order = Order::query()->find($orderId);
        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $paymentRecorder->recordPaymobCallbackResult($order, $result, null);

        if (($result['success'] ?? false) === true) {
            return response()->json(['message' => 'ok']);
        }

        return response()->json(['message' => 'failed'], 202);
    }
}
