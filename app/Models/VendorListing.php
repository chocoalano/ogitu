<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class VendorListing
 *
 * @property int $id
 * @property int $shop_id
 * @property int $product_variant_id
 * @property string $condition
 * @property float $price
 * @property float|null $promo_price
 * @property Carbon|null $promo_ends_at
 * @property int $qty_available
 * @property int $min_order_qty
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property ProductVariant $product_variant
 * @property Shop $shop
 * @property Collection|CartItem[] $cart_items
 * @property Collection|InventoryMovement[] $inventory_movements
 * @property Collection|OrderItem[] $order_items
 */
class VendorListing extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'vendor_listings';

    protected $casts = [
        'shop_id' => 'int',
        'product_variant_id' => 'int',
        'price' => 'float',
        'promo_price' => 'float',
        'promo_ends_at' => 'datetime',
        'qty_available' => 'int',
        'min_order_qty' => 'int',
    ];

    protected $fillable = [
        'shop_id',
        'product_variant_id',
        'condition',
        'price',
        'promo_price',
        'promo_ends_at',
        'qty_available',
        'min_order_qty',
        'status',
    ];

    public function product_variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function cart_items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function inventory_movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function order_items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
