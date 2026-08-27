<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\ValueObjects;

final readonly class RenderedTemplate
{
    public function __construct(
        public string $subject,
        public string $body,
    ) {}
}
