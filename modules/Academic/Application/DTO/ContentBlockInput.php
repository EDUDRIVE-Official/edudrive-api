<?php

declare(strict_types=1);

namespace Modules\Academic\Application\DTO;

final readonly class ContentBlockInput
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $id,
        public string $type,
        public int $position,
        public array $payload,
    ) {}
}
