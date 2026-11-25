<?php

namespace App\Modules\Payment\Gateways\Contracts;

use App\Modules\Payment\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Initiate payment with the payment provider.
     *
     * @return array<string, mixed>
     */
    public function initiate(
        Payment $payment
    ): array;

    /**
     * Verify a payment with the payment provider.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function verify(
        array $data
    ): array;
}

