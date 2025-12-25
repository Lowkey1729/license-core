<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'event',
        'action',
        'actor_type',
        'actor_id',
        'object_type',
        'object_id',
        'old_values',
        'new_values',
        'changed_field',
        'ip_address',
        'user_agent',
    ];
}
