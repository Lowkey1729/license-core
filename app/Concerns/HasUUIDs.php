<?php

namespace App\Concerns;

use Illuminate\Support\Str;

trait HasUUIDs
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function newUniqueId(): string
    {
        return strtoupper(Str::replace('-', '', Str::uuid7()->toString()));
    }
}
