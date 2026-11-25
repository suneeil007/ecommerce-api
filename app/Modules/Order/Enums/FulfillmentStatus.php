<?php

namespace App\Modules\Order\Enums;

enum FulfillmentStatus: string
{
    case UNFULFILLED = 'unfulfilled';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}