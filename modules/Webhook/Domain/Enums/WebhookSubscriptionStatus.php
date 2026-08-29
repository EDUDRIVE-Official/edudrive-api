<?php

declare(strict_types=1);

namespace Modules\Webhook\Domain\Enums;

enum WebhookSubscriptionStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
