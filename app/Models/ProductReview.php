<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductReview
 *
 * @property int $id
 * @property int $order_item_id
 * @property int $customer_id
 * @property int $rating
 * @property string|null $comment
 * @property Carbon $created_at
 * @property Customer $customer
 * @property OrderItem $order_item
 */
class ProductReview extends Model
{
    use HasFactory;

    protected $table = 'product_reviews';

    public $timestamps = false;

    protected $casts = [
        'order_item_id' => 'int',
        'customer_id' => 'int',
        'rating' => 'int',
    ];

    protected $fillable = [
        'order_item_id',
        'customer_id',
        'rating',
        'comment',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order_item()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
