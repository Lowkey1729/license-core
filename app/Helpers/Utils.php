<?php

declare(strict_types=1);

use App\Enums\ActorTypeEnum;
use App\Enums\EventEnum;
use App\Jobs\AuditLogJob;

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
