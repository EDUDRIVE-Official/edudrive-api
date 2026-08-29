<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class ListWebhookDeliveriesQuery implements Query
{
    public function __construct(
        public string $subscriptionId,
        public ?string $status = null,
    ) {}
}
