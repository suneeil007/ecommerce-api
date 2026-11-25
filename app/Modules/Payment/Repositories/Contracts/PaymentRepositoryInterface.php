<?php

namespace App\Modules\Payment\Repositories\Contracts;

use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Create Payment
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Payment;

    /*
    |--------------------------------------------------------------------------
    | Create Payment Transaction
    |--------------------------------------------------------------------------
    */

    public function createTransaction(
        Payment $payment,
        array $data
    ): PaymentTransaction;

    /*
    |--------------------------------------------------------------------------
    | Find Payment By UUID
    |--------------------------------------------------------------------------
    */

    public function findByUuid(string $uuid): ?Payment;

    /*
    |--------------------------------------------------------------------------
    | User - Single Payment
    |--------------------------------------------------------------------------
    */

    public function getUserPayment(
        int $userId,
        string $uuid
    ): ?Payment;

    /*
    |--------------------------------------------------------------------------
    | User - All Payments
    |--------------------------------------------------------------------------
    */

    public function getUserPayments(int $userId);

    /*
    |--------------------------------------------------------------------------
    | Admin - All Payments
    |--------------------------------------------------------------------------
    */

    public function getAllPayments();

    /*
    |--------------------------------------------------------------------------
    | Admin - Paginated Payments
    |--------------------------------------------------------------------------
    */

    public function getAdminPayments(
        array $filters
    ): LengthAwarePaginator;

    /*
    |--------------------------------------------------------------------------
    | Admin - Payment Statistics
    |--------------------------------------------------------------------------
    */

    public function getAdminStatistics(
        array $filters = []
    ): array;

    /*
    |--------------------------------------------------------------------------
    | Load Payment Relationships
    |--------------------------------------------------------------------------
    */

    public function loadPayment(
        Payment $payment
    ): Payment;
}
