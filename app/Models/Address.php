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
 * Class Address
 *
 * @property int $id
 * @property int $customer_id
 * @property string|null $label
 * @property string $recipient_name
 * @property string|null $phone
 * @property string $line1
 * @property string|null $line2
 * @property string $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string $country_code
 * @property bool $is_default_shipping
 * @property bool $is_default_billing
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Customer $customer
 * @property Collection|Order[] $orders
 * @property Collection|Shop[] $shops
 */
class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';

    protected $casts = [
        'customer_id' => 'int',
        'is_default_shipping' => 'bool',
        'is_default_billing' => 'bool',
    ];

    protected $fillable = [
        'customer_id',
        'label',
        'recipient_name',
        'phone',
        'line1',
        'line2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'is_default_shipping',
        'is_default_billing',
    ];

    protected static function booted()
    {
        static::saving(function ($addr) {
            if ($addr->is_default_shipping) {
                static::where('customer_id', $addr->customer_id)
                    ->whereKeyNot($addr->id)
                    ->update(['is_default_shipping' => false]);
            }
            if ($addr->is_default_billing) {
                static::where('customer_id', $addr->customer_id)
                    ->whereKeyNot($addr->id)
                    ->update(['is_default_billing' => false]);
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'shipping_address_id');
    }

    public function shops()
    {
        return $this->hasMany(Shop::class, 'pickup_address_id');
    }
}
