<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUUIDs;
use App\Helpers\LicenseKeyAESEncryption;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class LicenseKey extends Model
{
    use HasUUIDs;

    protected $fillable = [
        'brand_id',
        'customer_email',
        'key',
    ];

    protected function key(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => resolve(LicenseKeyAESEncryption::class)->encrypt($value),
        );
    }
}
