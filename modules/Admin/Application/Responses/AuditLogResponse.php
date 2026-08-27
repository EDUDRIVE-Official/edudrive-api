<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Responses;

use DateTimeInterface;
use Modules\Audit\Application\DTO\AuditEntry;

final readonly class AuditLogResponse
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public ?string $id,
        public string $action,
        public ?string $userId,
        public ?string $entity,
        public ?string $entityId,
        public array $metadata,
        public ?string $occurredAt,
    ) {}

    public static function fromAuditEntry(AuditEntry $entry): self
    {
        return new self(
            id: $entry->id,
            action: $entry->action,
            userId: $entry->userId,
            entity: $entry->entity,
            entityId: $entry->entityId,
            metadata: $entry->metadata,
            occurredAt: $entry->occurredAt?->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'user_id' => $this->userId,
            'entity' => $this->entity,
            'entity_id' => $this->entityId,
            'metadata' => $this->metadata,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
