<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * @property string $trace_id
 * @property string $url
 * @property string $method
 * @property string $ip_address
 * @property int $status_code
 * @property float $latency_ms
 * @property array<string, mixed>|null $request_body
 * @property array<string, mixed>|null $response_body
 */
class RequestTrace extends \MongoDB\Laravel\Eloquent\Model
{
    /**
     * The connection name for the model.
     *
     * @var UnitEnum|string|null
     */
    protected $connection = 'mongodb';

    protected $table = 'request_traces';

    protected $fillable = [
        'trace_id',
        'url',
        'method',
        'ip_address',
        'status_code',
        'latency_ms',
        'request_body',
        'response_body',
    ];

    protected $casts = [
        'request_body' => 'array',
        'response_body' => 'array',
        'created_at' => 'datetime',
    ];
}
