<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    protected $fillable = [
        'subject',
        'message',
        'status',
        'priority',
        'app_user_id',
        'vendor_id',
        'admin_reply',
        'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function senderName(): string
    {
        if ($this->appUser) return $this->appUser->name ?? 'User #' . $this->app_user_id;
        if ($this->vendor) return $this->vendor->restaurant_name ?? $this->vendor->full_name ?? 'Vendor #' . $this->vendor_id;
        return 'Unknown';
    }

    public function senderType(): string
    {
        if ($this->app_user_id) return 'User';
        if ($this->vendor_id) return 'Vendor';
        return 'Unknown';
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'open'        => '#3b82f6',
            'in_progress' => '#f59e0b',
            'resolved'    => '#22c55e',
            'closed'      => '#6b7280',
            default       => '#6b7280',
        };
    }

    public function priorityColor(): string
    {
        return match($this->priority) {
            'high'   => '#ef4444',
            'medium' => '#f59e0b',
            'low'    => '#22c55e',
            default  => '#6b7280',
        };
    }
}
