<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Delivery extends Authenticatable
{
    use HasFactory , HasApiTokens;

    protected $fillable = [
        'first_name',
        'email',
        'phone',
        'password',
        'photo',
        'city_id',
        'area_id',
        'drivers_license',
        'national_id',
        'vehicle_photo',
        'vehicle_type',
        'vehicle_license', // keep for backward compat if needed
        'vehicle_license_front',
        'vehicle_license_back',
        'vehicle_license', // JSON will be saved via accessor/mutator in controller
        'status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'vehicle_license' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    public function city()
    {
        return $this->belongsTo(\App\Models\City::class);
    }

    public function area()
    {
        return $this->belongsTo(\App\Models\Area::class);
    }
}
