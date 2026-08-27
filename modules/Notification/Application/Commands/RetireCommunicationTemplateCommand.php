<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RetireCommunicationTemplateCommand implements Command
{
    public function __construct(
        public string $templateId,
        public ?string $reason = null,
    ) {}
}
