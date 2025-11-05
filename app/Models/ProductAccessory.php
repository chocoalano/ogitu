<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductAccessory
 *
 * @property int $product_id
 * @property string $accessory_type
 * @property string|null $atomizer_type
 * @property Product $product
 */
class ProductAccessory extends Model
{
    use HasFactory;

    protected $table = 'product_accessories';

    protected $primaryKey = 'product_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'product_id' => 'int',
    ];

    protected $fillable = [
        'accessory_type',
        'atomizer_type',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
