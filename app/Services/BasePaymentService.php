<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BasePaymentService
{
    protected string $baseUrl = '';
    protected array $headers = [];

    protected function request(string $method, string $url, array $data = [], string $type = 'json'): Response
    {
        try {
            return Http::withHeaders($this->headers)->send($method, $this->baseUrl . $url, [
                $type => $data,
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }
}

