<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUUIDs;
use App\Helpers\BrandApiKeyAESEncryption;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandApiKey extends Model
{
    use HasUUIDs;

    protected $fillable = [
        'brand_id',
        'api_key',
        'expires_at',
    ];

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return Attribute<string, string>
     */
    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => resolve(BrandApiKeyAESEncryption::class)->decrypt($value),
            set: fn (string $value) => resolve(BrandApiKeyAESEncryption::class)->encrypt($value),
        );
    }
}
