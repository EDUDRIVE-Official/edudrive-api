<?php

declare(strict_types=1);

namespace Modules\Certification\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RevokeCertificateCommand implements Command
{
    public function __construct(
        public string $certificateId,
        public ?string $reason = null,
    ) {}
}
