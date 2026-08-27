<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateCommunicationTemplateCommand implements Command
{
    /** @param list<string> $variables */
    public function __construct(
        public string $code,
        public string $locale,
        public string $subjectTemplate,
        public string $bodyTemplate,
        public array $variables,
    ) {}
}
