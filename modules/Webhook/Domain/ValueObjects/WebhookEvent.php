<?php

declare(strict_types=1);

namespace Modules\Webhook\Domain\ValueObjects;

use DateTimeImmutable;
use Modules\Webhook\Domain\Enums\WebhookEventName;

final readonly class WebhookEvent
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public WebhookEventName $name,
        public array $payload,
        public DateTimeImmutable $occurredAt,
    ) {}
}
