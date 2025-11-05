<?php

namespace App\Enums;

enum ShopStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case SUSPENDED = 'suspended';
}
