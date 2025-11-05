<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ShipmentItem
 *
 * @property int $shipment_id
 * @property int $order_item_id
 * @property int $qty
 * @property OrderItem $order_item
 * @property Shipment $shipment
 */
class ShipmentItem extends Model
{
    use HasFactory;

    protected $table = 'shipment_items';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'shipment_id' => 'int',
        'order_item_id' => 'int',
        'qty' => 'int',
    ];

    protected $fillable = [
        'qty',
    ];

    public function order_item()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
