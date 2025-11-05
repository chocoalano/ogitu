<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class KycProfile
 *
 * @property int $id
 * @property int $customer_id
 * @property string $id_type
 * @property string $id_number
 * @property string $full_name_on_id
 * @property string $status
 * @property Carbon|null $verified_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Customer $customer
 */
class KycProfile extends Model
{
    use HasFactory;

    protected $table = 'kyc_profiles';

    protected $casts = [
        'customer_id' => 'int',
        'verified_at' => 'datetime',
    ];

    protected $fillable = [
        'customer_id',
        'id_type',
        'id_number',
        'full_name_on_id',
        'status',
        'verified_at',
        'notes',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
