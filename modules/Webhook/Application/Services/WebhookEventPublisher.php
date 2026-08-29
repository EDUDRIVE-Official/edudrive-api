<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\Services;

use Modules\Webhook\Domain\ValueObjects\WebhookEvent;

interface WebhookEventPublisher
{
    public function publish(WebhookEvent $event): void;
}
