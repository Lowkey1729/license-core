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

function formatKey(string $raw, int $length = 4): string
{
    return implode('-', str_split($raw, $length));
}
