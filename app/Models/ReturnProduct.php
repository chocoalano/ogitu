<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Return
 *
 * @property int $id
 * @property int $order_item_id
 * @property string|null $reason
 * @property string $status
 * @property int $qty
 * @property float $amount_requested
 * @property float|null $amount_refunded
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property OrderItem $order_item
 */
class ReturnProduct extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $casts = [
        'order_item_id' => 'int',
        'qty' => 'int',
        'amount_requested' => 'float',
        'amount_refunded' => 'float',
    ];

    protected $fillable = [
        'order_item_id',
        'reason',
        'status',
        'qty',
        'amount_requested',
        'amount_refunded',
    ];

    public function order_item()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
