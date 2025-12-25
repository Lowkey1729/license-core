<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activation extends Model
{
    protected $fillable = [
        'license_id',
        'fingerprint',
        'platform_info'
    ];
}
