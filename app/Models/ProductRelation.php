<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductRelation
 *
 * @property int $id
 * @property int $product_id
 * @property int $related_product_id
 * @property string $relation_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Product $product
 */
class ProductRelation extends Model
{
    use HasFactory;

    protected $table = 'product_relations';

    protected $casts = [
        'product_id' => 'int',
        'related_product_id' => 'int',
    ];

    protected $fillable = [
        'product_id',
        'related_product_id',
        'relation_type',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }

    /**
     * Produk terkait (B), foreign key: related_product_id
     */
    public function relatedProduct()
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }
}
