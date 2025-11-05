<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductRestriction
 *
 * @property int $id
 * @property int $product_id
 * @property string $country_code
 * @property string|null $state
 * @property string|null $city
 * @property int $min_age
 * @property bool $is_banned
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Product $product
 */
class ProductRestriction extends Model
{
    use HasFactory;

    protected $table = 'product_restrictions';

    protected $casts = [
        'product_id' => 'int',
        'min_age' => 'int',
        'is_banned' => 'bool',
    ];

    protected $fillable = [
        'product_id',
        'country_code',
        'state',
        'city',
        'min_age',
        'is_banned',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
