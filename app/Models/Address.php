<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'address_line_1',
        'address_line_2',
        'town_city',
        'region_state',
    ];

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
