<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUUIDs;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasUUIDs;

    protected $fillable = [
        'license_key_id',
        'product_id',
        'expires_at',
        'status',
        'max_seats',
    ];
}
