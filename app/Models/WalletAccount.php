<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WalletAccount
 *
 * @property int $id
 * @property string $owner_type
 * @property int $owner_id
 * @property string $currency
 * @property float $balance
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|Escrow[] $escrows
 * @property Collection|LedgerEntry[] $ledger_entries
 * @property Collection|Payout[] $payouts
 * @property Collection|Withdrawal[] $withdrawals
 */
class WalletAccount extends Model
{
    use HasFactory;

    protected $table = 'wallet_accounts';

    protected $casts = [
        'owner_id' => 'int',
        'balance' => 'float',
    ];

    protected $fillable = [
        'owner_type',
        'owner_id',
        'currency',
        'balance',
        'status',
    ];

    public function escrows()
    {
        return $this->hasMany(Escrow::class);
    }

    public function ledger_entries()
    {
        return $this->hasMany(LedgerEntry::class, 'account_id');
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }
}
