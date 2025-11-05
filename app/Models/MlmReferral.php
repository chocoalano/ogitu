<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlmReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'sponsor_member_id',
        'downline_member_id',
        'level',
        'referred_at',
    ];

    protected $casts = [
        'referred_at' => 'datetime',
    ];

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'sponsor_member_id');
    }

    public function downline(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'downline_member_id');
    }
}
