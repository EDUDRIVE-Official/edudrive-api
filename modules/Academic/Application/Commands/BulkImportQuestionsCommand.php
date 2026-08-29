<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class BulkImportQuestionsCommand implements Command
{
    /**
     * @param  list<array{competency_code: string, type: string, prompt: string, score: string, response: string, options: string, explanation: string, media: string, source_kind: string, source_reference: string, license_categories: string}>  $rows
     */
    public function __construct(
        public array $rows,
        public ?string $requestedByUserId,
    ) {}
}
