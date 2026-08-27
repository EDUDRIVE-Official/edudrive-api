<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Commands;

final readonly class BulkImportUsersCommand
{
    /** @param list<array{name: string, email: string, password: string, role: string}> $rows */
    public function __construct(
        public array $rows,
    ) {}
}
