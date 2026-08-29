<?php

declare(strict_types=1);

namespace Modules\Integration\Application\UseCases;

use Modules\Integration\Application\Exceptions\ApiConsumerNotFound;
use Modules\Integration\Application\Queries\GetApiConsumerQuery;
use Modules\Integration\Application\Responses\ApiConsumerResponse;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;

final readonly class GetApiConsumerHandler
{
    public function __construct(private ApiConsumerRepository $consumers) {}

    public function handle(GetApiConsumerQuery $query): ApiConsumerResponse
    {
        $consumer = $this->consumers->findById(ApiConsumerId::fromString($query->consumerId));
        if ($consumer === null) {
            throw ApiConsumerNotFound::withId($query->consumerId);
        }

        return ApiConsumerResponse::fromApiConsumer($consumer);
    }
}
