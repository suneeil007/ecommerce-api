<?php

namespace App\Modules\Payment\Enums;

enum PaymentMethod: string
{
    case COD = 'cod';
    case MANUAL = 'manual';
    case ESEWA = 'esewa';
    case KHALTI = 'khalti';
    case BANK_TRANSFER = 'bank_transfer';
}

