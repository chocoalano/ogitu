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
 * Class Cart
 *
 * @property int $id
 * @property int|null $customer_id
 * @property string|null $session_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|CartItem[] $cart_items
 */
class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected $casts = [
        'customer_id' => 'int',
    ];

    protected $fillable = [
        'customer_id',
        'session_id',
    ];

    public function cart_items()
    {
        return $this->hasMany(CartItem::class);
    }
}
