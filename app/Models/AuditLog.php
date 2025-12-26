<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * @property string $event
 * @property string $action
 * @property string $actor_type
 * @property int $actor_id
 * @property string $object_type
 * @property int $object_id
 * @property array<string, mixed>|null $metadata
 */
class AuditLog extends \MongoDB\Laravel\Eloquent\Model
{
    /**
     * The connection name for the model.
     *
     * @var UnitEnum|string|null
     */
    protected $connection = 'mongodb';

    protected $table = 'audit_logs';

    protected $fillable = [
        'event',
        'action',
        'actor_type',
        'actor_id',
        'object_type',
        'object_id',
        'metadata',
        'changed_field',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
