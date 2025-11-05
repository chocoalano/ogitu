<?php

namespace App\Enums;

enum ChargerType: string
{
    case TYPE_C = 'type-c';
    case MICRO_USB = 'micro-usb';
    case PROPRIETARY = 'proprietary';
}
