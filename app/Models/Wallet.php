<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'balance',
        'total_earned',
        'total_withdrawn',
    ];

    protected $casts = [
        'balance'          => 'decimal:2',
        'total_earned'     => 'decimal:2',
        'total_withdrawn'  => 'decimal:2',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    public function credit(float $amount, string $description, ?int $orderId = null, ?int $adminId = null): WalletTransaction
    {
        $this->balance       += $amount;
        $this->total_earned  += $amount;
        $this->save();

        return $this->transactions()->create([
            'type'          => 'credit',
            'amount'        => $amount,
            'balance_after' => $this->balance,
            'description'   => $description,
            'order_id'      => $orderId,
            'created_by'    => $adminId,
        ]);
    }

    public function debit(float $amount, string $description, ?int $orderId = null, ?int $adminId = null): WalletTransaction
    {
        $this->balance           -= $amount;
        $this->total_withdrawn   += $amount;
        $this->save();

        return $this->transactions()->create([
            'type'          => 'debit',
            'amount'        => $amount,
            'balance_after' => $this->balance,
            'description'   => $description,
            'order_id'      => $orderId,
            'created_by'    => $adminId,
        ]);
    }

    public static function forOwner(string $ownerType, int $ownerId): self
    {
        return self::firstOrCreate([
            'owner_type' => $ownerType,
            'owner_id'   => $ownerId,
        ], [
            'balance'         => 0,
            'total_earned'    => 0,
            'total_withdrawn' => 0,
        ]);
    }
}
