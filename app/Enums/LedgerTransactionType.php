<?php

namespace App\Enums;

enum LedgerTransactionType: string
{
    case TOPUP = 'topup';
    case PURCHASE_HOLD = 'purchase_hold';
    case PURCHASE_CAPTURE = 'purchase_capture';
    case REFUND = 'refund';
    case PAYOUT = 'payout';
    case WITHDRAWAL = 'withdrawal';
    case REVERSAL = 'reversal';
    case FEE_CAPTURE = 'fee_capture';
}
