<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AgeCheck
 *
 * @property int $id
 * @property int $customer_id
 * @property string $method
 * @property string $result
 * @property Carbon $checked_at
 * @property Customer $customer
 */
class AgeCheck extends Model
{
    use HasFactory;

    protected $table = 'age_checks';

    public $timestamps = false;

    protected $casts = [
        'customer_id' => 'int',
        'checked_at' => 'datetime',
    ];

    protected $fillable = [
        'customer_id',
        'method',
        'result',
        'checked_at',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
