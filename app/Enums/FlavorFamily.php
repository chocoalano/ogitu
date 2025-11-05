<?php

namespace App\Enums;

enum FlavorFamily: string
{
    case FRUIT = 'fruit';
    case DRINK = 'drink';
    case DESSERT = 'dessert';
    case MINT_ICE = 'mint_ice';
    case TOBACCO = 'tobacco';
    case OTHER = 'other';
}
