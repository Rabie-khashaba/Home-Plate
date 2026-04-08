<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PushNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'body', 'target_audience', 'type',
        'scheduled_at', 'recurrence_time', 'recurrence_day_of_week',
        'recurrence_week_of_month', 'recurrence_date',
        'status', 'sent_at', 'extra_data', 'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'       => 'datetime',
        'extra_data'    => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRecurring(): bool
    {
        return in_array($this->type, ['daily', 'weekly', 'monthly_day', 'monthly_date']);
    }

    public function typeLabel(): string
    {
        return match($this->type) {
            'immediate'    => 'Immediate',
            'scheduled'    => 'Scheduled',
            'daily'        => 'Daily',
            'weekly'       => 'Weekly',
            'monthly_day'  => 'Monthly (Day of Week)',
            'monthly_date' => 'Monthly (Date)',
            default        => $this->type,
        };
    }

    public function targetLabel(): string
    {
        return match($this->target_audience) {
            'all'     => 'All Users',
            'users'   => 'App Users',
            'vendors' => 'Vendors',
            'riders'  => 'Riders',
            default   => $this->target_audience,
        };
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'sent'    => 'badge-success',
            'failed'  => 'badge-danger',
            'active'  => 'badge-info',
            'pending' => 'badge-warning',
            default   => 'badge-secondary',
        };
    }
}
