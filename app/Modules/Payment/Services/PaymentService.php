<?php

namespace App\Modules\Payment\Services;

use App\Models\User;
use App\Modules\Order\Enums\PaymentStatus as OrderPaymentStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Enums\PaymentMethod;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Gateways\Esewa\EsewaGateway;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Repositories\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Resources\PaymentResource;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Create Payment
    |--------------------------------------------------------------------------
    */

    public function create(
        User $user,
        string $orderUuid,
        string $method,
        float $amount,
        ?string $reference = null,
        ?string $notes = null
    ): Payment {
        return DB::transaction(function () use (
            $user,
            $orderUuid,
            $method,
            $amount,
            $reference,
            $notes
        ) {
            if ($amount <= 0) {
                throw new RuntimeException(
                    'Payment amount must be greater than zero.'
                );
            }

            $paymentMethod = PaymentMethod::tryFrom($method);

            if (!$paymentMethod) {
                throw new RuntimeException(
                    'Invalid payment method.'
                );
            }

            /** @var Order|null $order */
            $order = Order::query()
                ->where('uuid', $orderUuid)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new RuntimeException(
                    'Order not found.'
                );
            }

            if ($order->status?->value === 'cancelled') {
                throw new RuntimeException(
                    'Cannot make payment for a cancelled order.'
                );
            }

            if ($order->payment_status?->value === 'paid') {
                throw new RuntimeException(
                    'This order is already fully paid.'
                );
            }

            $paidAmount = (float) $order->payments()
                ->whereIn('status', [
                    PaymentStatus::PAID->value,
                    PaymentStatus::PARTIALLY_PAID->value,
                ])
                ->sum('amount');

            $orderTotal = (float) $order->total_amount;

            $remainingAmount = round(
                $orderTotal - $paidAmount,
                2
            );

            if ($remainingAmount <= 0) {
                throw new RuntimeException(
                    'No payment balance remains for this order.'
                );
            }

            if ($amount > $remainingAmount) {
                throw new RuntimeException(
                    "Payment amount cannot exceed the remaining balance of {$remainingAmount}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Determine Payment Status
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | This method treats the payment as already received.
            | Do not use it for unverified online gateway payments.
            |
            */

            $paymentStatus = (
                $amount >= $remainingAmount
            )
                ? PaymentStatus::PAID
                : PaymentStatus::PARTIALLY_PAID;

            /*
            |--------------------------------------------------------------------------
            | Create Payment
            |--------------------------------------------------------------------------
            */

            $payment = $this->paymentRepository->create([
                'order_id' => $order->id,
                'method' => $paymentMethod->value,
                'status' => $paymentStatus->value,
                'currency' => $order->currency,
                'amount' => $amount,
                'reference' => $reference,
                'notes' => $notes,
                'paid_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Payment Transaction
            |--------------------------------------------------------------------------
            */

            $this->paymentRepository->createTransaction(
                $payment,
                [
                    'provider' => $paymentMethod->value,
                    'transaction_id' => $reference,
                    'status' => $paymentStatus->value,
                    'amount' => $amount,
                    'currency' => $order->currency,
                    'response_data' => null,
                    'failure_reason' => null,
                    'processed_at' => now(),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Update Order Payment Status
            |--------------------------------------------------------------------------
            */

            $newPaidAmount = round(
                $paidAmount + $amount,
                2
            );

            if ($newPaidAmount >= $orderTotal) {
                $order->payment_status =
                    OrderPaymentStatus::PAID;
            } elseif ($newPaidAmount > 0) {
                $order->payment_status =
                    OrderPaymentStatus::PARTIALLY_PAID;
            } else {
                $order->payment_status =
                    OrderPaymentStatus::PENDING;
            }

            $order->save();

            return $this->paymentRepository
                ->loadPayment($payment);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | User - Single Payment
    |--------------------------------------------------------------------------
    */

    public function getUserPayment(
        User $user,
        string $uuid
    ): Payment {
        $payment = $this->paymentRepository
            ->getUserPayment(
                $user->id,
                $uuid
            );

        if (!$payment) {
            throw new RuntimeException(
                'Payment not found.'
            );
        }

        return $payment;
    }

    /*
    |--------------------------------------------------------------------------
    | User - All Payments
    |--------------------------------------------------------------------------
    */

    public function getUserPayments(
        User $user
    ) {
        return $this->paymentRepository
            ->getUserPayments(
                $user->id
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Admin - All Payments (Legacy)
    |--------------------------------------------------------------------------
    */

    public function getAllPayments()
    {
        return $this->paymentRepository
            ->getAllPayments();
    }

    /*
    |--------------------------------------------------------------------------
    | Admin - Paginated Payments
    |--------------------------------------------------------------------------
    */

    public function getAdminPayments(
        array $filters
    ) {
        return $this->paymentRepository
            ->getAdminPayments($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin - Payment Statistics
    |--------------------------------------------------------------------------
    */

    public function getAdminStatistics(
        array $filters = []
    ): array {
        return $this->paymentRepository
            ->getAdminStatistics($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin - Single Payment
    |--------------------------------------------------------------------------
    */

    public function getPaymentByUuid(
        string $uuid
    ): Payment {
        $payment = $this->paymentRepository
            ->findByUuid($uuid);

        if (!$payment) {
            throw new RuntimeException(
                'Payment not found.'
            );
        }

        return $this->paymentRepository
            ->loadPayment($payment);
    }

    /*
    |--------------------------------------------------------------------------
    | eSewa - Initiate Payment
    |--------------------------------------------------------------------------
    */

    public function initiateEsewa(
        User $user,
        string $orderUuid
    ): array {
        return DB::transaction(function () use (
            $user,
            $orderUuid
        ) {
            /** @var Order|null $order */
            $order = Order::query()
                ->where('uuid', $orderUuid)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new RuntimeException(
                    'Order not found.'
                );
            }

            if ($order->status?->value === 'cancelled') {
                throw new RuntimeException(
                    'Cannot make payment for a cancelled order.'
                );
            }

            if ($order->payment_status?->value === 'paid') {
                throw new RuntimeException(
                    'This order is already fully paid.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate Remaining Amount
            |--------------------------------------------------------------------------
            */

            $paidAmount = (float) $order->payments()
                ->whereIn('status', [
                    PaymentStatus::PAID->value,
                    PaymentStatus::PARTIALLY_PAID->value,
                ])
                ->sum('amount');

            $orderTotal = (float) $order->total_amount;

            $remainingAmount = round(
                $orderTotal - $paidAmount,
                2
            );

            if ($remainingAmount <= 0) {
                throw new RuntimeException(
                    'No payment balance remains for this order.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create Pending eSewa Payment
            |--------------------------------------------------------------------------
            */

            $payment = $this->paymentRepository->create([
                'order_id' => $order->id,
                'method' => PaymentMethod::ESEWA->value,
                'status' => PaymentStatus::PENDING->value,
                'currency' => $order->currency,
                'amount' => $remainingAmount,
                'reference' => null,
                'notes' => null,
                'paid_at' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Pending Transaction
            |--------------------------------------------------------------------------
            */

            $this->paymentRepository->createTransaction(
                $payment,
                [
                    'provider' => 'esewa',
                    'transaction_id' => $payment->uuid,
                    'status' => PaymentStatus::PENDING->value,
                    'amount' => $remainingAmount,
                    'currency' => $order->currency,
                    'response_data' => null,
                    'failure_reason' => null,
                    'processed_at' => null,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Initiate eSewa Gateway
            |--------------------------------------------------------------------------
            */

            $gateway = app(EsewaGateway::class);

            $gatewayData = $gateway->initiate($payment);

            return [
                'payment' => new PaymentResource(
                    $this->paymentRepository
                        ->loadPayment($payment)
                ),

                'gateway' => $gatewayData,
            ];
        });
    }
}
