<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class LedgerEntry
 *
 * @property int $id
 * @property int $ledger_transaction_id
 * @property int $account_id
 * @property string $direction
 * @property float $amount
 * @property float|null $balance_after
 * @property string|null $memo
 * @property Carbon $created_at
 * @property WalletAccount $wallet_account
 * @property LedgerTransaction $ledger_transaction
 */
class LedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'ledger_entries';

    public $timestamps = false;

    protected $casts = [
        'ledger_transaction_id' => 'int',
        'account_id' => 'int',
        'amount' => 'float',
        'balance_after' => 'float',
    ];

    protected $fillable = [
        'ledger_transaction_id',
        'account_id',
        'direction',
        'amount',
        'balance_after',
        'memo',
    ];

    public function wallet_account()
    {
        return $this->belongsTo(WalletAccount::class, 'account_id');
    }

    public function ledger_transaction()
    {
        return $this->belongsTo(LedgerTransaction::class);
    }
}
