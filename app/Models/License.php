<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUUIDs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<LicenseKey, $this>
     */
    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class);
    }

    /**
     * @return HasMany<Activation, $this>
     */
    public function activations(): HasMany
    {
        return $this->hasMany(Activation::class);
    }

    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * @param  Builder<License>  $query
     * @return Builder<License>
     */
    public function scopeWhereProduct(Builder $query, ?string $productSlug): Builder
    {
        return $query->when(
            $productSlug,
            fn (Builder $q) => $q->whereRelation('product', 'slug', $productSlug)
        );
    }
}
