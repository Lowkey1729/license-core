<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandApiKey extends Model
{
    protected $fillable = [
        'brand_id',
        'key'
    ];
}
