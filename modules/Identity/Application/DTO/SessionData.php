<?php

declare(strict_types=1);

namespace Modules\Identity\Application\DTO;

final readonly class SessionData
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $current,
        public ?string $lastUsedAt,
        public string $createdAt,
    ) {}
}
