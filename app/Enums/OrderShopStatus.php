<?php

namespace App\Enums;

enum OrderShopStatus: string
{
    case AWAITING_PAYMENT = 'awaiting_payment';
    case AWAITING_FULFILLMENT = 'awaiting_fulfillment';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
}
