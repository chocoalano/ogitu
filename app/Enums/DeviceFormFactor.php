<?php

namespace App\Enums;

enum DeviceFormFactor: string
{
    case MOD = 'mod';
    case POD_SYSTEM = 'pod_system';
    case POD_REFILLABLE = 'pod_refillable';
    case DISPOSABLE = 'disposable';
    case AIO = 'aio';
}
