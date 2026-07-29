<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Contracts;

use Modules\Audit\Application\DTO\AuditEntry;

interface AuditRepository
{
    public function save(AuditEntry $entry): void;
}
