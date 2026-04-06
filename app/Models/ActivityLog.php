<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'properties',
        'ip_address',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionColor(): string
    {
        return match(true) {
            str_contains($this->action, 'create') || str_contains($this->action, 'add')    => '#22c55e',
            str_contains($this->action, 'delete') || str_contains($this->action, 'reject')  => '#ef4444',
            str_contains($this->action, 'approve')                                           => '#3b82f6',
            str_contains($this->action, 'send')                                              => '#8b5cf6',
            str_contains($this->action, 'login')                                             => '#10b981',
            str_contains($this->action, 'logout')                                            => '#6b7280',
            str_contains($this->action, 'update') || str_contains($this->action, 'edit')    => '#f59e0b',
            default                                                                          => '#6b7280',
        };
    }

    public function actionIcon(): string
    {
        return match(true) {
            str_contains($this->action, 'create') => '＋',
            str_contains($this->action, 'delete') => '✕',
            str_contains($this->action, 'update') => '✎',
            str_contains($this->action, 'approve') => '✔',
            str_contains($this->action, 'reject') => '✘',
            str_contains($this->action, 'send') => '➤',
            str_contains($this->action, 'login') => '→',
            str_contains($this->action, 'logout') => '←',
            default => '●',
        };
    }
}
