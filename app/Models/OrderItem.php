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
 * Class OrderItem
 *
 * @property int $id
 * @property int $order_shop_id
 * @property int $product_variant_id
 * @property int $vendor_listing_id
 * @property string $name
 * @property string $sku
 * @property int $qty
 * @property float $unit_price
 * @property float $discount_amount
 * @property float $tax_amount
 * @property float $total
 * @property array|null $attributes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property OrderShop $order_shop
 * @property ProductVariant $product_variant
 * @property VendorListing $vendor_listing
 * @property Collection|ProductReview[] $product_reviews
 * @property Collection|Refund[] $refunds
 * @property Collection|ReturnProduct[] $returns
 * @property Collection|ShipmentItem[] $shipment_items
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $casts = [
        'order_shop_id' => 'int',
        'product_variant_id' => 'int',
        'vendor_listing_id' => 'int',
        'qty' => 'int',
        'unit_price' => 'float',
        'discount_amount' => 'float',
        'tax_amount' => 'float',
        'total' => 'float',
        'attributes' => 'json',
    ];

    protected $fillable = [
        'order_shop_id',
        'product_variant_id',
        'vendor_listing_id',
        'name',
        'sku',
        'qty',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'total',
        'attributes',
    ];

    public function order_shop()
    {
        return $this->belongsTo(OrderShop::class);
    }

    public function product_variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function vendor_listing()
    {
        return $this->belongsTo(VendorListing::class);
    }

    public function product_reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function returns()
    {
        return $this->hasMany(ReturnProduct::class);
    }

    public function shipment_items()
    {
        return $this->hasMany(ShipmentItem::class);
    }
}
