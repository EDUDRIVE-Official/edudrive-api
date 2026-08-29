<?php

declare(strict_types=1);

namespace Modules\Webhook\Domain\Enums;

enum WebhookDeliveryStatus: string
{
    case Pending = 'pending';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case DeadLettered = 'dead_lettered';
}
