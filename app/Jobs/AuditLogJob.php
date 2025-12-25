<?php

namespace App\Jobs;

use App\Enums\ActorTypeEnum;
use App\Enums\EventEnum;
use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly EventEnum $event,
        private readonly string $action,
        private readonly ActorTypeEnum $actorType,
        private readonly ?string $actorId = null,
        private readonly ?string $objectType = null,
        private readonly ?string $objectId = null
    ) {}

    public function handle(): void
    {
        AuditLog::query()->create([
            'event' => $this->event->value,
            'action' => $this->action,
            'actor_type' => $this->actorType->value,
            'actor_id' => $this->actorId,
            'object_type' => $this->objectType,
            'object_id' => $this->objectId,
        ]);
    }
}
