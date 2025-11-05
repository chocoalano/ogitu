<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductLiquid
 *
 * @property int $product_id
 * @property string $intended_device
 * @property string $flavor_family
 * @property int|null $bottle_size_ml
 * @property Product $product
 */
class ProductLiquid extends Model
{
    use HasFactory;

    protected $table = 'product_liquids';

    protected $primaryKey = 'product_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'product_id' => 'int',
        'bottle_size_ml' => 'int',
    ];

    protected $fillable = [
        'intended_device',
        'flavor_family',
        'bottle_size_ml',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
