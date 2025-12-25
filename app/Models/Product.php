<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUUIDs;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasUUIDs;

    protected $fillable = [
        'brand_id',
        'name',
        'slug',
    ];
}
