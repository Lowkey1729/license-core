<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUUIDs;
use Illuminate\Database\Eloquent\Model;

class Activation extends Model
{
    use HasUUIDs;

    protected $fillable = [
        'license_id',
        'fingerprint',
        'platform_info',
    ];
}
