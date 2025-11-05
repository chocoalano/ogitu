<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case WALLET = 'wallet';
    case GATEWAY = 'gateway';
    case COD = 'cod';
}
