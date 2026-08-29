<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetWebhookSubscriptionQuery implements Query
{
    public function __construct(
        public string $subscriptionId,
    ) {}
}
