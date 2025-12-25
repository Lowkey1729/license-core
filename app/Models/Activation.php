<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUUIDs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activation extends Model
{
    use HasUUIDs, SoftDeletes;

    protected $fillable = [
        'license_id',
        'fingerprint',
        'platform_info',
    ];
}
