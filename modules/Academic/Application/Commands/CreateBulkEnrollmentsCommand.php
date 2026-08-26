<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateBulkEnrollmentsCommand implements Command
{
    /** @param list<string> $userIds */
    public function __construct(
        public string $courseId,
        public array $userIds,
        public string $status = 'pending',
        public string $source = 'bulk',
        public ?string $startsAt = null,
        public ?string $endsAt = null,
    ) {}
}
