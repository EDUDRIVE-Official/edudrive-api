<?php

declare(strict_types=1);

namespace Modules\Integration\Application\UseCases;

use DateTimeImmutable;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Integration\Application\Commands\SuspendApiConsumerCommand;
use Modules\Integration\Application\Exceptions\ApiConsumerNotFound;
use Modules\Integration\Application\Responses\ApiConsumerResponse;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;

final readonly class SuspendApiConsumerHandler
{
    public function __construct(
        private ApiConsumerRepository $consumers,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(SuspendApiConsumerCommand $command): ApiConsumerResponse
    {
        $consumer = $this->consumers->findById(ApiConsumerId::fromString($command->consumerId));
        if ($consumer === null) {
            throw ApiConsumerNotFound::withId($command->consumerId);
        }

        $consumer->suspend($command->reason, new DateTimeImmutable('now'));
        $this->consumers->save($consumer);

        $this->auditLogger->log(
            new AuditEntry(
                action: 'integration.api_consumer_suspended',
                userId: $command->actorId,
                entity: 'ApiConsumer',
                entityId: $consumer->id()->value(),
                metadata: ['reason' => $command->reason],
            ),
        );

        return ApiConsumerResponse::fromApiConsumer($consumer);
    }
}
