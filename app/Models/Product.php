<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Product
 *
 * @property int $id
 * @property int|null $brand_id
 * @property int $primary_category_id
 * @property string $type
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_age_restricted
 * @property array|null $specs
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property Brand|null $brand
 * @property Category $category
 * @property ProductAccessory|null $product_accessory
 * @property ProductDevice|null $product_device
 * @property ProductLiquid|null $product_liquid
 * @property Collection|ProductRelation[] $product_relations
 * @property Collection|ProductRestriction[] $product_restrictions
 * @property Collection|ProductVariant[] $product_variants
 */
class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'products';

    protected $casts = [
        'brand_id' => 'int',
        'primary_category_id' => 'int',
        'is_active' => 'bool',
        'is_age_restricted' => 'bool',
        'specs' => 'json',
    ];

    protected $fillable = [
        'brand_id',
        'primary_category_id',
        'type',
        'name',
        'slug',
        'description',
        'is_active',
        'is_age_restricted',
        'specs',
    ];

    /** Semua media milik produk ini. */
    public function media(): HasMany
    {
        return $this->hasMany(Medium::class, 'owner_id')
            ->where('owner_type', 'product')
            ->orderBy('position')
            ->orderBy('id');
    }

    /** Satu gambar utama (thumbnail) berdasar urutan position. */
    public function thumbnail(): HasOne
    {
        return $this->hasOne(Medium::class, 'owner_id')
            ->where('owner_type', 'product')
            ->orderBy('position')
            ->orderBy('id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    public function product_accessory()
    {
        return $this->hasOne(ProductAccessory::class);
    }

    public function product_device()
    {
        return $this->hasOne(ProductDevice::class);
    }

    public function product_liquid()
    {
        return $this->hasOne(ProductLiquid::class);
    }

    public function product_relations()
    {
        return $this->hasMany(ProductRelation::class, 'related_product_id');
    }

    public function product_restrictions()
    {
        return $this->hasMany(ProductRestriction::class);
    }

    public function product_variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): Builder
    {
        return ProductReview::query()
            ->select('product_reviews.*')
            ->join('order_items', 'order_items.id', '=', 'product_reviews.order_item_id')
            ->join('order_shops', 'order_shops.id', '=', 'order_items.order_shop_id')
            ->join('orders', 'orders.id', '=', 'order_shops.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('product_variants.product_id', $this->getKey());

        // Jika ingin hanya pesanan tertentu (opsional), aktifkan salah satu contoh ini:
        // ->where('orders.payment_status', 'paid')
        // ->whereIn('orders.status', ['processing', 'completed'])
        // ->whereNull('orders.deleted_at'); // kalau soft delete
    }

    /**
     * Helper untuk rata-rata rating (opsional).
     */
    public function averageRating(): float
    {
        return (float) ($this->reviews()->avg('product_reviews.rating') ?? 0.0);
    }
}
