<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class PreviewCommunicationTemplateQuery implements Query
{
    /** @param array<string, string> $variables */
    public function __construct(
        public string $templateId,
        public array $variables,
    ) {}
}
