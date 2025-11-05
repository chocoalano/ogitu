<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Refund
 *
 * @property int $id
 * @property int|null $order_shop_id
 * @property int|null $order_item_id
 * @property float $amount
 * @property string|null $reason
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property OrderItem|null $order_item
 * @property OrderShop|null $order_shop
 */
class Refund extends Model
{
    use HasFactory;

    protected $table = 'refunds';

    protected $casts = [
        'order_shop_id' => 'int',
        'order_item_id' => 'int',
        'amount' => 'float',
    ];

    protected $fillable = [
        'order_shop_id',
        'order_item_id',
        'amount',
        'reason',
        'status',
    ];

    public function order_item()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function order_shop()
    {
        return $this->belongsTo(OrderShop::class);
    }
}
