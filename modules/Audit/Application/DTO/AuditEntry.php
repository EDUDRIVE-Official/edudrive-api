<?php

declare(strict_types=1);

namespace Modules\Audit\Application\DTO;

use DateTimeImmutable;

final readonly class AuditEntry
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $action,
        public ?string $userId = null,
        public ?string $entity = null,
        public ?string $entityId = null,
        public array $metadata = [],
        public ?string $id = null,
        public ?DateTimeImmutable $occurredAt = null,
    ) {}
}
