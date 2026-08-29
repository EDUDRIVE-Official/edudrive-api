<?php

declare(strict_types=1);

namespace Modules\Integration\Domain\Repositories;

use Modules\Integration\Domain\Aggregates\ApiConsumer;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;

interface ApiConsumerRepository
{
    public function save(ApiConsumer $consumer): void;

    public function findById(ApiConsumerId $id): ?ApiConsumer;

    public function findByIntegrationKeyHash(string $integrationKeyHash): ?ApiConsumer;

    /** @return list<ApiConsumer> */
    public function all(): array;
}
