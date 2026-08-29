<?php

declare(strict_types=1);

namespace Modules\Integration\Application\UseCases;

use Modules\Integration\Application\Queries\ListApiConsumersQuery;
use Modules\Integration\Application\Responses\ApiConsumerResponse;
use Modules\Integration\Domain\Aggregates\ApiConsumer;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;

final readonly class ListApiConsumersHandler
{
    public function __construct(private ApiConsumerRepository $consumers) {}

    /** @return list<ApiConsumerResponse> */
    public function handle(ListApiConsumersQuery $query): array
    {
        return array_map(
            static fn (ApiConsumer $consumer): ApiConsumerResponse => ApiConsumerResponse::fromApiConsumer($consumer),
            $this->consumers->all(),
        );
    }
}
