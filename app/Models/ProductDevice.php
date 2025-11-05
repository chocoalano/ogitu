<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductDevice
 *
 * @property int $product_id
 * @property string $form_factor
 * @property string|null $battery_type
 * @property int|null $battery_size_mah
 * @property string|null $external_battery_format
 * @property int|null $watt_min
 * @property int|null $watt_max
 * @property string|null $charger_type
 * @property Product $product
 */
class ProductDevice extends Model
{
    use HasFactory;

    protected $table = 'product_devices';

    protected $primaryKey = 'product_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'product_id' => 'int',
        'battery_size_mah' => 'int',
        'watt_min' => 'int',
        'watt_max' => 'int',
    ];

    protected $fillable = [
        'form_factor',
        'battery_type',
        'battery_size_mah',
        'external_battery_format',
        'watt_min',
        'watt_max',
        'charger_type',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
