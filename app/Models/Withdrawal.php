<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Withdrawal
 *
 * @property int $id
 * @property int $wallet_account_id
 * @property float $amount
 * @property string $bank_code
 * @property string $account_number
 * @property string $account_name
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property WalletAccount $wallet_account
 */
class Withdrawal extends Model
{
    use HasFactory;

    protected $table = 'withdrawals';

    protected $casts = [
        'wallet_account_id' => 'int',
        'amount' => 'float',
    ];

    protected $fillable = [
        'wallet_account_id',
        'amount',
        'bank_code',
        'account_number',
        'account_name',
        'status',
    ];

    public function wallet_account()
    {
        return $this->belongsTo(WalletAccount::class);
    }
}
