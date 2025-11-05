<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wishlist extends Model
{
    protected $fillable = [
        'customer_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function wishlist_items(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function items(): HasMany
    {
        return $this->wishlist_items();
    }
}
