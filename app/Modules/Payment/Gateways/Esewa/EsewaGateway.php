<?php

namespace App\Modules\Payment\Gateways\Esewa;

use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Gateways\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class EsewaGateway implements PaymentGatewayInterface
{
    /**
     * Initiate eSewa payment.
     *
     * eSewa ePay V2 uses a POST form submission.
     *
     * @return array<string, mixed>
     */
    public function initiate(
        Payment $payment
    ): array {
        $secretKey = config(
            'payment.esewa.secret_key'
        );

        $productCode = config(
            'payment.esewa.product_code'
        );

        if (!$secretKey) {
            throw new RuntimeException(
                'eSewa secret key is not configured.'
            );
        }

        if (!$productCode) {
            throw new RuntimeException(
                'eSewa product code is not configured.'
            );
        }

        $transactionUuid = $payment->uuid;

        $amount = number_format(
            (float) $payment->amount,
            2,
            '.',
            ''
        );

        $taxAmount = '0.00';
        $serviceCharge = '0.00';
        $deliveryCharge = '0.00';

        $totalAmount = number_format(
            (float) $payment->amount,
            2,
            '.',
            ''
        );

        /*
        |--------------------------------------------------------------------------
        | eSewa Signature
        |--------------------------------------------------------------------------
        |
        | eSewa requires these fields in exactly this order:
        |
        | total_amount
        | transaction_uuid
        | product_code
        |
        */

        $signedFieldNames = implode(',', [
            'total_amount',
            'transaction_uuid',
            'product_code',
        ]);

        $signatureMessage = implode(',', [
            'total_amount=' . $totalAmount,
            'transaction_uuid=' . $transactionUuid,
            'product_code=' . $productCode,
        ]);

        $signature = base64_encode(
            hash_hmac(
                'sha256',
                $signatureMessage,
                $secretKey,
                true
            )
        );

        return [
            'gateway' => 'esewa',

            'payment_url' => config(
                'payment.esewa.payment_url'
            ),

            'method' => 'POST',

            'fields' => [
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,

                'transaction_uuid' => $transactionUuid,

                'product_code' => $productCode,

                'product_service_charge' => $serviceCharge,
                'product_delivery_charge' => $deliveryCharge,

                'success_url' => config(
                    'payment.esewa.success_url'
                ),

                'failure_url' => config(
                    'payment.esewa.failure_url'
                ),

                'signed_field_names' => $signedFieldNames,

                'signature' => $signature,
            ],
        ];
    }

    /**
     * Verify eSewa payment.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function verify(
        array $data
    ): array {
        $transactionUuid =
            $data['transaction_uuid']
            ?? null;

        $totalAmount =
            $data['total_amount']
            ?? null;

        $productCode =
            $data['product_code']
            ?? config(
                'payment.esewa.product_code'
            );

        if (!$transactionUuid) {
            throw new RuntimeException(
                'eSewa transaction UUID is required.'
            );
        }

        if ($totalAmount === null) {
            throw new RuntimeException(
                'eSewa total amount is required.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Verify using eSewa status API
        |--------------------------------------------------------------------------
        */

        $response = Http::timeout(
            config(
                'payment.esewa.timeout',
                30
            )
        )->get(
            config(
                'payment.esewa.status_url'
            ),
            [
                'product_code' =>
                    $productCode,

                'total_amount' =>
                    $totalAmount,

                'transaction_uuid' =>
                    $transactionUuid,
            ]
        );

        if (!$response->successful()) {
            throw new RuntimeException(
                'Unable to verify eSewa payment.'
            );
        }

        $responseData = $response->json();

        if (!is_array($responseData)) {
            throw new RuntimeException(
                'Invalid response received from eSewa.'
            );
        }

        $status =
            strtoupper(
                (string) (
                    $responseData['status']
                    ?? ''
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Map eSewa status to our application status
        |--------------------------------------------------------------------------
        */

        $paymentStatus = match ($status) {
            'COMPLETE' =>
                PaymentStatus::PAID,

            'PENDING' =>
                PaymentStatus::PROCESSING,

            'FULL_REFUND' =>
                PaymentStatus::REFUNDED,

            'PARTIAL_REFUND' =>
                PaymentStatus::PARTIALLY_PAID,

            'CANCELED',
            'CANCELLED',
            'NOT_FOUND' =>
                PaymentStatus::FAILED,

            default =>
                PaymentStatus::FAILED,
        };

        return [
            'success' =>
                $paymentStatus === PaymentStatus::PAID,

            'status' =>
                $paymentStatus->value,

            'gateway_status' =>
                $status,

            'transaction_uuid' =>
                $transactionUuid,

            'reference' =>
                $responseData['ref_id']
                ?? $responseData['refId']
                ?? null,

            'amount' =>
                isset($responseData['total_amount'])
                    ? (float) $responseData['total_amount']
                    : (
                        isset($responseData['totalAmount'])
                            ? (float) $responseData['totalAmount']
                            : (float) $totalAmount
                    ),

            'response' =>
                $responseData,
        ];
    }
}

