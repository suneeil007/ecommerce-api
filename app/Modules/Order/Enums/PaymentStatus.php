<?php

namespace App\Modules\Order\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
}