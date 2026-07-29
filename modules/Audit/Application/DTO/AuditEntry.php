<?php

declare(strict_types=1);

namespace Modules\Audit\Application\DTO;

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
    ) {}
}
