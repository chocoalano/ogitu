<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Shop
 *
 * @property int $id
 * @property int $vendor_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $pickup_address_id
 * @property float $rating_avg
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Address|null $address
 * @property Vendor $vendor
 * @property Collection|Coupon[] $coupons
 * @property Collection|Order[] $orders
 * @property Collection|VendorListing[] $vendor_listings
 */
class Shop extends Model
{
    use HasFactory;

    protected $table = 'shops';

    protected $casts = [
        'vendor_id' => 'int',
        'pickup_address_id' => 'int',
        'rating_avg' => 'float',
    ];

    protected $fillable = [
        'vendor_id',
        'name',
        'slug',
        'description',
        'pickup_address_id',
        'rating_avg',
        'status',
    ];

    public function address()
    {
        return $this->belongsTo(Address::class, 'pickup_address_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_shops')
            ->withPivot('id', 'subtotal', 'shipping_cost', 'discount_total', 'tax_total', 'commission_fee', 'status', 'escrow_id')
            ->withTimestamps();
    }

    public function vendor_listings()
    {
        return $this->hasMany(VendorListing::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Medium::class, 'owner_id')
            ->where('owner_type', 'shop')
            ->orderBy('position')
            ->orderBy('id');
    }

    public function thumbnail(): HasOne
    {
        return $this->hasOne(Medium::class, 'owner_id')
            ->where('owner_type', 'shop')
            ->orderBy('position')
            ->orderBy('id');
    }
}
