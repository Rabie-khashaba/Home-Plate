<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'app_user_id',
        'order_id',
        'rating',
        'review',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
};
