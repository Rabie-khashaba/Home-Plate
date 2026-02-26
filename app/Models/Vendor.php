<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Vendor extends Authenticatable
{
    use HasFactory , HasApiTokens;

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'password',
        'otp_code',
        'otp_expires_at',
        'id_front',
        'id_back',
        'restaurant_info',
        'main_photo',
        'restaurant_name',
        'city_id',
        'area_id',
        'delivery_address',
        'location',
        'kitchen_photo_1',
        'kitchen_photo_2',
        'kitchen_photo_3',
        'working_time',
        'status',
        'is_active',
    ];
    
    protected $casts = [
         'is_active' => 'boolean',
         'otp_expires_at' => 'datetime',
         'working_time' => 'array',
    ];


    protected $hidden = ['password'];

    // 🔹 العلاقات
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    
        // 🔹 التشفير
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

}
