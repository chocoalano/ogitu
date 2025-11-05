<?php

namespace App\Enums;

enum ProductType: string
{
    case DEVICE = 'device';
    case LIQUID = 'liquid';
    case ACCESSORY = 'accessory';
}
