<?php

namespace App\Modules\Payment\Repositories;

use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Payment\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRepository implements PaymentRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Create Payment
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Payment Transaction
    |--------------------------------------------------------------------------
    */

    public function createTransaction(
        Payment $payment,
        array $data
    ): PaymentTransaction {
        return $payment->transactions()->create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Find Payment By UUID
    |--------------------------------------------------------------------------
    */

    public function findByUuid(string $uuid): ?Payment
    {
        return Payment::findByUuid($uuid);
    }

    /*
    |--------------------------------------------------------------------------
    | User - Single Payment
    |--------------------------------------------------------------------------
    */

    public function getUserPayment(
        int $userId,
        string $uuid
    ): ?Payment {
        return Payment::query()
            ->where('uuid', $uuid)
            ->whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with([
                'order',
                'transactions',
            ])
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | User - All Payments
    |--------------------------------------------------------------------------
    */

    public function getUserPayments(int $userId)
    {
        return Payment::query()
            ->whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with([
                'order',
                'transactions',
            ])
            ->latest('id')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Admin - All Payments (Legacy)
    |--------------------------------------------------------------------------
    */

    public function getAllPayments()
    {
        return Payment::query()
            ->with([
                'order',
                'order.user',
                'transactions',
            ])
            ->latest('id')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Admin - Paginated Payments
    |--------------------------------------------------------------------------
    */

    public function getAdminPayments(
        array $filters
    ): LengthAwarePaginator {
        $query = Payment::query()
            ->with([
                'order',
                'order.user',
                'transactions',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($query) use ($search) {

                // Payment reference
                $query->where(
                    'reference',
                    'like',
                    "%{$search}%"
                )

                // Payment UUID
                ->orWhere(
                    'uuid',
                    'like',
                    "%{$search}%"
                )

                // Order details and customer information
                ->orWhereHas('order', function ($orderQuery) use ($search) {

                    $orderQuery
                        ->where(
                            'order_number',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'customer_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'phone',
                            'like',
                            "%{$search}%"
                        )

                        // Registered customer details
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                );
                        });
                })

                // Payment transaction details
                ->orWhereHas('transactions', function ($transactionQuery) use ($search) {
                    $transactionQuery
                        ->where(
                            'transaction_id',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'provider',
                            'like',
                            "%{$search}%"
                        );
                });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filter By Payment Status
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter By Payment Method
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['method'])) {
            $query->where(
                'method',
                $filters['method']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter By Date Range
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['from'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $filters['from']
            );
        }

        if (!empty($filters['to'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['to']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'created_at',
            'amount',
            'paid_at',
            'status',
        ];

        $requestedSort = $filters['sort'] ?? 'created_at';

        $sort = in_array(
            $requestedSort,
            $allowedSorts,
            true
        )
            ? $requestedSort
            : 'created_at';

        $direction = ($filters['direction'] ?? 'desc') === 'asc'
            ? 'asc'
            : 'desc';

        $query->orderBy($sort, $direction)
            ->orderBy('id', 'desc');

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = min(
            max((int) ($filters['per_page'] ?? 15), 1),
            100
        );

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Admin - Payment Statistics
    |--------------------------------------------------------------------------
    */

    public function getAdminStatistics(
        array $filters = []
    ): array {
        $query = Payment::query();

        /*
        |--------------------------------------------------------------------------
        | Date Filters
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['from'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $filters['from']
            );
        }

        if (!empty($filters['to'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['to']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Aggregate Payment Statistics
        |--------------------------------------------------------------------------
        */

        $statistics = $query
            ->selectRaw('COUNT(*) as total_payments')

            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END),
                    0
                ) as successful_payments
            ")

            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN status = 'partially_paid' THEN 1 ELSE 0 END),
                    0
                ) as partially_paid_payments
            ")

            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END),
                    0
                ) as pending_payments
            ")

            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END),
                    0
                ) as processing_payments
            ")

            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END),
                    0
                ) as failed_payments
            ")

            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END),
                    0
                ) as cancelled_payments
            ")

            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END),
                    0
                ) as refunded_payments
            ")

            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END),
                    0
                ) as successful_amount
            ")

            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN status = 'partially_paid' THEN amount ELSE 0 END),
                    0
                ) as partially_paid_amount
            ")

            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END),
                    0
                ) as pending_amount
            ")

            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END),
                    0
                ) as failed_amount
            ")

            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END),
                    0
                ) as refunded_amount
            ")

            ->first();

        /*
        |--------------------------------------------------------------------------
        | Return Statistics
        |--------------------------------------------------------------------------
        */

        return [
            'total_payments' => (int) $statistics->total_payments,

            'successful_payments' => (int) $statistics->successful_payments,

            'partially_paid_payments' => (int) $statistics->partially_paid_payments,

            'pending_payments' => (int) $statistics->pending_payments,

            'processing_payments' => (int) $statistics->processing_payments,

            'failed_payments' => (int) $statistics->failed_payments,

            'cancelled_payments' => (int) $statistics->cancelled_payments,

            'refunded_payments' => (int) $statistics->refunded_payments,

            'successful_amount' => (float) $statistics->successful_amount,

            'partially_paid_amount' => (float) $statistics->partially_paid_amount,

            'pending_amount' => (float) $statistics->pending_amount,

            'failed_amount' => (float) $statistics->failed_amount,

            'refunded_amount' => (float) $statistics->refunded_amount,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Load Payment Relationships
    |--------------------------------------------------------------------------
    */

    public function loadPayment(
        Payment $payment
    ): Payment {
        return $payment->load([
            'order',
            'order.user',
            'transactions',
        ]);
    }
}
