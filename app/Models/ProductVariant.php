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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProductVariant
 *
 * @property int $id
 * @property int $product_id
 * @property string $sku
 * @property string|null $barcode
 * @property string $name
 * @property string|null $color
 * @property float|null $capacity_ml
 * @property string|null $nicotine_type
 * @property int|null $nicotine_mg
 * @property int|null $vg_ratio
 * @property int|null $pg_ratio
 * @property float|null $coil_resistance_ohm
 * @property int|null $puff_count
 * @property array|null $specs
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property Product $product
 * @property Collection|OrderItem[] $order_items
 * @property Collection|VendorListing[] $vendor_listings
 */
class ProductVariant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'product_variants';

    protected $casts = [
        'product_id' => 'int',
        'capacity_ml' => 'float',
        'nicotine_mg' => 'int',
        'vg_ratio' => 'int',
        'pg_ratio' => 'int',
        'coil_resistance_ohm' => 'float',
        'puff_count' => 'int',
        'specs' => 'json',
        'is_active' => 'bool',
    ];

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'name',
        'color',
        'capacity_ml',
        'nicotine_type',
        'nicotine_mg',
        'vg_ratio',
        'pg_ratio',
        'coil_resistance_ohm',
        'puff_count',
        'specs',
        'is_active',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order_items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function vendor_listings()
    {
        return $this->hasMany(VendorListing::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Medium::class, 'owner_id')
            ->where('owner_type', 'variant')
            ->orderBy('position')
            ->orderBy('id');
    }

    public function thumbnail(): HasOne
    {
        return $this->hasOne(Medium::class, 'owner_id')
            ->where('owner_type', 'variant')
            ->orderBy('position')
            ->orderBy('id');
    }
}
