<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Services;

use Modules\Audit\Application\DTO\AuditEntry;

interface AuditLogger
{
    public function log(AuditEntry $entry): void;
}
