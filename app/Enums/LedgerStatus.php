<?php

namespace App\Enums;

enum LedgerStatus: string
{
    case PENDING = 'pending';
    case POSTED = 'posted';
    case VOID = 'void';
}
