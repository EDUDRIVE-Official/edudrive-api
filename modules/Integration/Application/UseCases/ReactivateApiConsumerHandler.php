<?php

declare(strict_types=1);

namespace Modules\Integration\Application\UseCases;

use DateTimeImmutable;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Integration\Application\Commands\ReactivateApiConsumerCommand;
use Modules\Integration\Application\Exceptions\ApiConsumerNotFound;
use Modules\Integration\Application\Responses\ApiConsumerResponse;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;

final readonly class ReactivateApiConsumerHandler
{
    public function __construct(
        private ApiConsumerRepository $consumers,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(ReactivateApiConsumerCommand $command): ApiConsumerResponse
    {
        $consumer = $this->consumers->findById(ApiConsumerId::fromString($command->consumerId));
        if ($consumer === null) {
            throw ApiConsumerNotFound::withId($command->consumerId);
        }

        $consumer->reactivate(new DateTimeImmutable('now'));
        $this->consumers->save($consumer);

        $this->auditLogger->log(
            new AuditEntry(
                action: 'integration.api_consumer_reactivated',
                userId: $command->actorId,
                entity: 'ApiConsumer',
                entityId: $consumer->id()->value(),
            ),
        );

        return ApiConsumerResponse::fromApiConsumer($consumer);
    }
}
