<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Escrow
 *
 * @property int $id
 * @property int $order_shop_id
 * @property int $wallet_account_id
 * @property float $amount_held
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property OrderShop $order_shop
 * @property WalletAccount $wallet_account
 */
class Escrow extends Model
{
    use HasFactory;

    protected $table = 'escrows';

    protected $casts = [
        'order_shop_id' => 'int',
        'wallet_account_id' => 'int',
        'amount_held' => 'float',
    ];

    protected $fillable = [
        'order_shop_id',
        'wallet_account_id',
        'amount_held',
        'status',
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
