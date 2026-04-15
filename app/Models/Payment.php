<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'order_id',
        'provider',
        'method',
        'amount',
        'currency',
        'status',
        'reference',
        'provider_order_id',
        'provider_transaction_id',
        'payment_key',
        'iframe_url',
        'paid_at',
        'failed_at',
        'canceled_at',
        'refunded_at',
        'provider_payload',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'provider_payload' => 'array',
        'meta' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

