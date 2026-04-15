<?php

namespace App\Interfaces;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Creates a hosted payment/invoice link.
     *
     * @return array{success:bool,url?:string,provider_order_id?:string,message?:string,raw?:mixed}
     */
    public function sendPayment(Request $request): array;

    /**
     * Parses Paymob redirect/webhook payload and returns normalized result.
     *
     * @return array{success:bool,order_id?:int,merchant_order_id?:string,provider_order_id?:string,provider_transaction_id?:string,message?:string,raw:array}
     */
    public function callBack(Request $request): array;
}

