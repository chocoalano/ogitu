<?php

namespace App\Enums;

enum ListingStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case OUT_OF_STOCK = 'out_of_stock';
    case BANNED = 'banned';
}
