<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Shipment
 *
 * @property int $id
 * @property int $order_shop_id
 * @property string $courier_code
 * @property string|null $service_name
 * @property string|null $tracking_no
 * @property Carbon|null $shipped_at
 * @property Carbon|null $delivered_at
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property OrderShop $order_shop
 * @property Collection|ShipmentItem[] $shipment_items
 */
class Shipment extends Model
{
    use HasFactory;

    protected $table = 'shipments';

    protected $casts = [
        'order_shop_id' => 'int',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected $fillable = [
        'order_shop_id',
        'courier_code',
        'service_name',
        'tracking_no',
        'shipped_at',
        'delivered_at',
        'status',
    ];

    public function order_shop()
    {
        return $this->belongsTo(OrderShop::class);
    }

    public function shipment_items()
    {
        return $this->hasMany(ShipmentItem::class);
    }
}
