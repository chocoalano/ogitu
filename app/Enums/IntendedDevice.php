<?php

namespace App\Enums;

enum IntendedDevice: string
{
    case MOD = 'mod';
    case POD = 'pod';
    case BOTH = 'both';
}
