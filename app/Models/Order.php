<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING_VENDOR_PREPARATION = 'pending_vendor_preparation';
    public const STATUS_SEARCHING_DELIVERY = 'searching_delivery';
    public const STATUS_DELIVERY_ASSIGNED = 'delivery_assigned';
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const STATUS_HANDOVER_PENDING_CONFIRMATION = 'handover_pending_confirmation';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'app_user_id',
        'vendor_id',
        'delivery_id',
        'order_cost',
        'delivery_fee',
        'total_amount',
        'payment_method',
        'payment_status',
        'payment_reference',
        'delivery_address',
        'ordered_at',
        'status',
        'delivery_pin',
        'pin_verified_at',
        'started_cooking_at',
        'delivery_requested_at',
        'delivery_accepted_at',
        'ready_for_pickup_at',
        'vendor_handover_confirmed_at',
        'delivery_pickup_confirmed_at',
        'picked_up_at',
        'out_for_delivery_at',
        'delivered_at',
        'notes',
    ];

    protected $casts = [
        'order_cost' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'pin_verified_at' => 'datetime',
        'started_cooking_at' => 'datetime',
        'delivery_requested_at' => 'datetime',
        'delivery_accepted_at' => 'datetime',
        'ready_for_pickup_at' => 'datetime',
        'vendor_handover_confirmed_at' => 'datetime',
        'delivery_pickup_confirmed_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING_VENDOR_PREPARATION,
            self::STATUS_SEARCHING_DELIVERY,
            self::STATUS_DELIVERY_ASSIGNED,
            self::STATUS_READY_FOR_PICKUP,
            self::STATUS_HANDOVER_PENDING_CONFIRMATION,
            self::STATUS_PICKED_UP,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ];
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    public function customer(): BelongsTo
    {
        return $this->appUser();
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->latest();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transitionTo(
        string $toStatus,
        ?string $actorType = null,
        ?int $actorId = null,
        ?string $action = null,
        ?string $note = null,
        ?array $meta = null
    ): void {
        $fromStatus = $this->status;

        if ($fromStatus === $toStatus) {
            return;
        }

        $this->status = $toStatus;
        $this->save();

        $this->statusLogs()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'note' => $note,
            'meta' => $meta,
        ]);
    }

    public function statusLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'vodafone_cash' => 'Vodafone Cash',
            'instapay' => 'InstaPay',
            'visa' => 'Visa',
            default => ucwords(str_replace('_', ' ', (string) $this->payment_method)),
        };
    }
}
