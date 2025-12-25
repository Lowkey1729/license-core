<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUUIDs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasUUIDs;

    protected $fillable = [
        'brand_id',
        'name',
        'slug',
    ];

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
