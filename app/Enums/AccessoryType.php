<?php

namespace App\Enums;

enum AccessoryType: string
{
    case ATOMIZER = 'atomizer';
    case TANK = 'tank';
    case CARTRIDGE = 'cartridge';
    case COIL = 'coil';
    case COTTON = 'cotton';
    case BATTERY = 'battery';
    case CHARGER = 'charger';
    case TOOLS = 'tools';
    case REPLACEMENT_POD = 'replacement_pod';
}
