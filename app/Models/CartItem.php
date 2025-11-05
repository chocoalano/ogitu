<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CartItem
 *
 * @property int $id
 * @property int $cart_id
 * @property int $vendor_listing_id
 * @property int $qty
 * @property float $price_snapshot
 * @property array|null $variant_snapshot
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Cart $cart
 * @property VendorListing $vendor_listing
 */
class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cart_items';

    protected $casts = [
        'cart_id' => 'int',
        'vendor_listing_id' => 'int',
        'qty' => 'int',
        'price_snapshot' => 'float',
        'variant_snapshot' => 'json',
    ];

    protected $fillable = [
        'cart_id',
        'vendor_listing_id',
        'qty',
        'price_snapshot',
        'variant_snapshot',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function vendor_listing()
    {
        return $this->belongsTo(VendorListing::class);
    }
}
