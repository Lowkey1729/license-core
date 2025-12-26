<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUUIDs;
use App\Helpers\LicenseKeyAESEncryption;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseKey extends Model
{
    use HasUUIDs;

    protected $fillable = [
        'brand_id',
        'customer_email',
        'key',
    ];

    /**
     * @return HasMany<License, $this>
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /**
     * @return Attribute<string, string>
     */
    protected function key(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? resolve(LicenseKeyAESEncryption::class)->decrypt($value) : null,
            set: fn (string $value) => resolve(LicenseKeyAESEncryption::class)->encrypt($value),
        );
    }
}
