<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class BulkImportCoursesCommand implements Command
{
    /**
     * @param  list<array{code: string, title: string, description: string, objectives: string, prerequisites: string, modality: string, duration_hours: string}>  $rows
     */
    public function __construct(
        public array $rows,
    ) {}
}
