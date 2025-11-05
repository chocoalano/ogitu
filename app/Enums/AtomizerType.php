<?php

namespace App\Enums;

enum AtomizerType: string
{
    case RDA = 'rda';
    case RTA = 'rta';
    case RDTA = 'rdta';
    case TANK = 'tank';
}
