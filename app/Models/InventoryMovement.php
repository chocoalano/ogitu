<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class InventoryMovement
 *
 * @property int $id
 * @property int $vendor_listing_id
 * @property string $type
 * @property int $qty
 * @property string|null $ref_type
 * @property int|null $ref_id
 * @property string|null $note
 * @property Carbon $created_at
 * @property VendorListing $vendor_listing
 */
class InventoryMovement extends Model
{
    use HasFactory;

    protected $table = 'inventory_movements';

    public $timestamps = false;

    protected $casts = [
        'vendor_listing_id' => 'int',
        'qty' => 'int',
        'ref_id' => 'int',
    ];

    protected $fillable = [
        'vendor_listing_id',
        'type',
        'qty',
        'ref_type',
        'ref_id',
        'note',
    ];

    public function vendor_listing()
    {
        return $this->belongsTo(VendorListing::class);
    }
}
