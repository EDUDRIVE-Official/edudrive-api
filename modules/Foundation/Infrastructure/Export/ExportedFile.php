<?php

declare(strict_types=1);

namespace Modules\Foundation\Infrastructure\Export;

use DateTimeImmutable;

final readonly class ExportedFile
{
    public function __construct(
        public string $url,
        public DateTimeImmutable $expiresAt,
    ) {}
}
