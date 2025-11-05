<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MlmMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'sponsor_id',
        'level',
        'total_downlines',
        'total_commission_earned',
        'pending_commission',
        'status',
        'joined_at',
    ];

    protected $casts = [
        'total_commission_earned' => 'decimal:2',
        'pending_commission' => 'decimal:2',
        'joined_at' => 'datetime',
    ];

    // Helper to get customer code for referral
    public function getReferralCodeAttribute(): ?string
    {
        return $this->customer?->customer_code;
    }

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'sponsor_id');
    }

    public function downlines(): HasMany
    {
        return $this->hasMany(MlmMember::class, 'sponsor_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(MlmReferral::class, 'sponsor_member_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(MlmCommission::class);
    }

    // Helper methods
    public function updateDownlineCount(): void
    {
        $this->total_downlines = $this->referrals()->count();
        $this->save();
    }

    public function getUpline(int $level = 1): ?MlmMember
    {
        $current = $this->sponsor;
        for ($i = 1; $i < $level && $current; $i++) {
            $current = $current->sponsor;
        }

        return $current;
    }
}
