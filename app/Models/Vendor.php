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
 * Class Vendor
 *
 * @property int $id
 * @property int $customer_id
 * @property string $company_name
 * @property string|null $npwp
 * @property string|null $phone
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Customer $customer
 * @property Collection|Shop[] $shops
 */
class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendors';

    protected $casts = [
        'customer_id' => 'int',
    ];

    protected $fillable = [
        'customer_id',
        'company_name',
        'npwp',
        'phone',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function shops()
    {
        return $this->hasMany(Shop::class);
    }
}
