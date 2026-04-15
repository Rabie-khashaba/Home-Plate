<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PaymobPaymentService extends BasePaymentService implements PaymentGatewayInterface
{
    protected string $apiKey;
    protected array $integrationIds;
    protected string $currency;

    public function __construct()
    {
        $base = (string) config('paymob.base_url', 'https://accept.paymob.com/api');
        $base = rtrim($base, '/');
        $this->baseUrl = preg_replace('~/api$~', '', $base) ?: $base;

        $this->apiKey = (string) config('paymob.api_key', env('BAYMOB_API_KEY'));
        $this->currency = (string) config('paymob.currency', 'EGP');

        $rawIntegrationId = config('paymob.integration_id', null);
        if (is_string($rawIntegrationId) && str_contains($rawIntegrationId, ',')) {
            $this->integrationIds = array_values(array_filter(array_map('intval', array_map('trim', explode(',', $rawIntegrationId)))));
        } else {
            $this->integrationIds = array_values(array_filter([is_numeric($rawIntegrationId) ? (int) $rawIntegrationId : null]));
        }

        // Fallback (keeps old behavior if env not set)
        if ($this->integrationIds === []) {
            $this->integrationIds = [5481966];
        }

        $this->headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function generateToken(): string
    {
        $response = $this->request('POST', '/api/auth/tokens', ['api_key' => $this->apiKey]);
        $data = $response->json();

        return (string) Arr::get($data, 'token', '');
    }

    protected function normalizeAmount(array $data): array
    {
        if (isset($data['amount'])) {
            $data['amount_cents'] = (int) round(((float) $data['amount']) * 100);
            unset($data['amount']);
        } elseif (isset($data['amount_cents'])) {
            $data['amount_cents'] = (int) round(((float) $data['amount_cents']) * 100);
        }

        return $data;
    }

    public function sendPayment(Request $request): array
    {
        $token = $this->generateToken();
        if ($token === '') {
            return ['success' => false, 'message' => 'Failed to generate Paymob token.'];
        }

        $this->headers['Authorization'] = 'Bearer ' . $token;

        $data = $this->normalizeAmount($request->all());
        $data['currency'] = $data['currency'] ?? $this->currency;
        $data['api_source'] = 'INVOICE';
        $data['integrations'] = $this->integrationIds;

        $response = $this->request('POST', '/api/ecommerce/orders', $data);
        $payload = $response->json();

        if ($response->successful()) {
            $url = (string) Arr::get($payload, 'url', Arr::get($payload, 'data.url', ''));
            $providerOrderId = Arr::get($payload, 'id', Arr::get($payload, 'data.id'));

            if ($url !== '') {
                return [
                    'success' => true,
                    'url' => $url,
                    'provider_order_id' => $providerOrderId !== null ? (string) $providerOrderId : null,
                    'raw' => $payload,
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Failed to create Paymob payment link.',
            'raw' => $payload,
        ];
    }

    public function callBack(Request $request): array
    {
        $raw = $request->all();

        $successValue = $request->input('success', Arr::get($raw, 'obj.success', null));
        $success = $successValue === true || $successValue === 'true' || $successValue === 1 || $successValue === '1';

        $merchantOrderId = (string) ($request->input('merchant_order_id')
            ?? Arr::get($raw, 'merchant_order_id')
            ?? Arr::get($raw, 'obj.order.merchant_order_id')
            ?? Arr::get($raw, 'order.merchant_order_id')
            ?? '');

        $providerOrderId = $request->input('order')
            ?? Arr::get($raw, 'order')
            ?? Arr::get($raw, 'obj.order.id')
            ?? Arr::get($raw, 'obj.order')
            ?? null;

        $providerTransactionId = $request->input('id')
            ?? Arr::get($raw, 'id')
            ?? Arr::get($raw, 'obj.id')
            ?? Arr::get($raw, 'obj.transaction.id')
            ?? null;

        $orderId = null;
        if (preg_match('/\bORDER-(\d+)\b/i', $merchantOrderId, $m)) {
            $orderId = (int) $m[1];
        }

        return [
            'success' => $success,
            'order_id' => $orderId,
            'merchant_order_id' => $merchantOrderId !== '' ? $merchantOrderId : null,
            'provider_order_id' => $providerOrderId !== null ? (string) $providerOrderId : null,
            'provider_transaction_id' => $providerTransactionId !== null ? (string) $providerTransactionId : null,
            'raw' => $raw,
        ];
    }
}

