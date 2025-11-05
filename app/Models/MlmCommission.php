<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlmCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'mlm_member_id',
        'order_id',
        'commission_type',
        'amount',
        'from_level',
        'from_member_id',
        'description',
        'status',
        'earned_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'earned_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function fromMember(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'from_member_id');
    }
}
