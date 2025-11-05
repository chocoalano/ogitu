<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Payout
 *
 * @property int $id
 * @property int $order_shop_id
 * @property int $wallet_account_id
 * @property float $gross_amount
 * @property float $fee_platform
 * @property float $net_amount
 * @property string $status
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property OrderShop $order_shop
 * @property WalletAccount $wallet_account
 */
class Payout extends Model
{
    use HasFactory;

    protected $table = 'payouts';

    protected $casts = [
        'order_shop_id' => 'int',
        'wallet_account_id' => 'int',
        'gross_amount' => 'float',
        'fee_platform' => 'float',
        'net_amount' => 'float',
        'paid_at' => 'datetime',
    ];

    protected $fillable = [
        'order_shop_id',
        'wallet_account_id',
        'gross_amount',
        'fee_platform',
        'net_amount',
        'status',
        'paid_at',
    ];

    public function order_shop()
    {
        return $this->belongsTo(OrderShop::class);
    }

    public function wallet_account()
    {
        return $this->belongsTo(WalletAccount::class);
    }
}
