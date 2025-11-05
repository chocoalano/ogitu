<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Topup
 *
 * @property int $id
 * @property int $customer_id
 * @property float $amount
 * @property string $gateway
 * @property string|null $gateway_ref
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Customer $customer
 */
class Topup extends Model
{
    use HasFactory;

    protected $table = 'topups';

    protected $casts = [
        'customer_id' => 'int',
        'amount' => 'float',
    ];

    protected $fillable = [
        'customer_id',
        'amount',
        'gateway',
        'gateway_ref',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
