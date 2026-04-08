<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'value'            => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount'     => 'decimal:2',
        'starts_at'        => 'datetime',
        'expires_at'       => 'datetime',
        'is_active'        => 'boolean',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isUsageLimitReached(): bool
    {
        return $this->usage_limit && $this->used_count >= $this->usage_limit;
    }

    public function statusLabel(): string
    {
        if (!$this->is_active) return 'Inactive';
        if ($this->isExpired()) return 'Expired';
        if ($this->isUsageLimitReached()) return 'Exhausted';
        if ($this->starts_at && $this->starts_at->isFuture()) return 'Scheduled';
        return 'Active';
    }

    public function statusColor(): string
    {
        return match($this->statusLabel()) {
            'Active'    => '#22c55e',
            'Scheduled' => '#3b82f6',
            'Expired'   => '#ef4444',
            'Exhausted' => '#f59e0b',
            default     => '#6b7280',
        };
    }
}
