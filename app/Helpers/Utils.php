<?php

declare(strict_types=1);

use App\Enums\ActorTypeEnum;
use App\Enums\EventEnum;
use App\Jobs\AuditLogJob;
use Illuminate\Support\Str;

/**
 * @param  array<string, mixed>|null  $metadata
 */
function auditLog(
    EventEnum $event,
    string $action,
    ActorTypeEnum $actorType,
    ?string $actorId = null,
    ?string $objectType = null,
    ?string $objectId = null,
    ?array $metadata = null,
    bool $dispatchAfterCommit = false,
): void {

    $job = AuditLogJob::dispatch(
        $event,
        $action,
        $actorType,
        $actorId,
        $objectType,
        $objectId,
        $metadata,
    );

    if ($dispatchAfterCommit) {
        $job->afterCommit();
    }
}

function newUniqueId(): string
{
    return strtoupper(Str::replace('-', '', Str::uuid7()->toString()));
}

/**
 * @param  positive-int  $length
 */
function formatKey(string $raw, int $length = 4): string
{
    return implode('-', str_split($raw, $length));
}

/**
 * @param  array<int|string, mixed>  $data
 * @return array<int|string, mixed>
 */
function secureData(array $data): array
{
    $protectedKeys = ['refresh_token', 'token', 'client_id', 'client_secret', 'customer_email', 'license_key'];

    foreach ($protectedKeys as $key) {
        if (isset($data[$key])) {
            $data[$key] = Str::mask($data[$key], '***', 7, 30);
        }
    }

    return $data;
}
