<?php

namespace App\Enums;

enum WalletOwnerType: string
{
    case CUSTOMER = 'customer';
    case SHOP = 'shop';
    case PLATFORM = 'platform';
    case ESCROW = 'escrow';
}
