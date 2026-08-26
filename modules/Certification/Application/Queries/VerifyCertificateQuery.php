<?php

declare(strict_types=1);

namespace Modules\Certification\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class VerifyCertificateQuery implements Query
{
    public function __construct(
        public string $validationCode,
    ) {}
}
