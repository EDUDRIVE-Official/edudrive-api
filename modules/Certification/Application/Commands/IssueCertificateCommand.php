<?php

declare(strict_types=1);

namespace Modules\Certification\Application\Commands;

use DateTimeImmutable;
use Modules\Foundation\Application\Commands\Command;

final readonly class IssueCertificateCommand implements Command
{
    public function __construct(
        public string $userId,
        public string $courseId,
        public ?DateTimeImmutable $expiresAt = null,
    ) {}
}
