<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Class Order
 *
 * @property int $id
 * @property int $customer_id
 * @property string $order_no
 * @property int $shipping_address_id
 * @property int|null $billing_address_id
 * @property float $subtotal
 * @property float $shipping_total
 * @property float $discount_total
 * @property float $tax_total
 * @property float $grand_total
 * @property string $payment_method
 * @property string $payment_status
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Address $address
 * @property Customer $customer
 * @property Collection|Shop[] $shops
 */
class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $casts = [
        'customer_id' => 'int',
        'shipping_address_id' => 'int',
        'billing_address_id' => 'int',
        'subtotal' => 'float',
        'shipping_total' => 'float',
        'discount_total' => 'float',
        'tax_total' => 'float',
        'grand_total' => 'float',
    ];

    protected $fillable = [
        'customer_id',
        'order_no',
        'shipping_address_id',
        'billing_address_id',
        'subtotal',
        'shipping_total',
        'discount_total',
        'tax_total',
        'grand_total',
        'payment_method',
        'payment_status',
        'status',
    ];

    public function order_items(): HasManyThrough
    {
        return $this->hasManyThrough(
            OrderItem::class,   // related model
            OrderShop::class,   // through / perantara
            'order_id',         // FK di tabel order_shops yang merujuk ke orders
            'order_shop_id',    // FK di tabel order_items yang merujuk ke order_shops
            'id',               // local key di orders
            'id'                // local key di order_shops
        );
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'order_shops')
            ->withPivot('id', 'subtotal', 'shipping_cost', 'discount_total', 'tax_total', 'commission_fee', 'status', 'escrow_id')
            ->withTimestamps();
    }

    public function order_shops()
    {
        return $this->hasMany(OrderShop::class, 'order_id');
    }
}
