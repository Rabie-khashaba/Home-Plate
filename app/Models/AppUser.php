<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class AppUser extends Authenticatable
{
    use HasFactory, Notifiable , HasApiTokens;

    protected $guard = 'app_user';

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'gender',
        'otp_code', 'otp_expires_at', 'photo', 'dob', 'city_id', 'area_id', 'delivery_addresses','location', 'is_active'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'dob' => 'date',
        'is_active' => 'boolean',
        'otp_expires_at' => 'datetime',
    ];
    
    
    public function city()
    {
        return $this->belongsTo(City::class);
    }


    // 🔹 علاقة المستخدم مع المنطقة
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
