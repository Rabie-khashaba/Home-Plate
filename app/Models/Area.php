<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'name_en',
        'name_ar',
        'delivery_fee',
        'min_order_amount',
        'estimated_minutes',
        'is_active',
    ];

    protected $casts = [
        'delivery_fee'     => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'is_active'        => 'boolean',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
