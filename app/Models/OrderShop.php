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
 * Class OrderShop
 *
 * @property int $id
 * @property int $order_id
 * @property int $shop_id
 * @property float $subtotal
 * @property float $shipping_cost
 * @property float $discount_total
 * @property float $tax_total
 * @property float $commission_fee
 * @property string $status
 * @property int|null $escrow_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Order $order
 * @property Shop $shop
 * @property Collection|Escrow[] $escrows
 * @property Collection|OrderItem[] $order_items
 * @property Collection|Payout[] $payouts
 * @property Collection|Refund[] $refunds
 * @property Collection|Shipment[] $shipments
 */
class OrderShop extends Model
{
    use HasFactory;

    protected $table = 'order_shops';

    protected $casts = [
        'order_id' => 'int',
        'shop_id' => 'int',
        'subtotal' => 'float',
        'shipping_cost' => 'float',
        'discount_total' => 'float',
        'tax_total' => 'float',
        'commission_fee' => 'float',
        'escrow_id' => 'int',
    ];

    protected $fillable = [
        'order_id',
        'shop_id',
        'subtotal',
        'shipping_cost',
        'discount_total',
        'tax_total',
        'commission_fee',
        'status',
        'escrow_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function escrows()
    {
        return $this->hasMany(Escrow::class);
    }

    public function order_items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }
}
