<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Coupon
 *
 * @property int $id
 * @property string $code
 * @property string $type
 * @property float $value
 * @property string $applies_to
 * @property int|null $shop_id
 * @property float|null $min_order
 * @property int|null $max_uses
 * @property int $used
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Shop|null $shop
 */
class Coupon extends Model
{
    use HasFactory;

    protected $table = 'coupons';

    protected $casts = [
        'value' => 'float',
        'shop_id' => 'int',
        'min_order' => 'float',
        'max_uses' => 'int',
        'used' => 'int',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected $fillable = [
        'code',
        'type',
        'value',
        'applies_to',
        'shop_id',
        'min_order',
        'max_uses',
        'used',
        'starts_at',
        'ends_at',
        'status',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
