<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'license_key_id',
        'product_id',
        'expires_at',
        'status',
        'max_seats'
    ];
}
