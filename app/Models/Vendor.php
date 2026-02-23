<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Vendor extends Authenticatable
{
    use HasFactory , HasApiTokens;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'city_id', 'area_id',
        'logo', 'address', 'location', 'status', 'is_active'
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


        // 🔹 التشفير
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

}
