<?php

declare(strict_types=1);

namespace Modules\Integration\Application\UseCases;

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Integration\Application\Commands\RotateApiConsumerIntegrationKeyCommand;
use Modules\Integration\Application\Exceptions\ApiConsumerNotFound;
use Modules\Integration\Application\Responses\ApiConsumerResponse;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;
use Modules\Integration\Domain\ValueObjects\IntegrationKey;

final readonly class RotateApiConsumerIntegrationKeyHandler
{
    public function __construct(
        private ApiConsumerRepository $consumers,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(RotateApiConsumerIntegrationKeyCommand $command): ApiConsumerResponse
    {
        $consumer = $this->consumers->findById(ApiConsumerId::fromString($command->consumerId));
        if ($consumer === null) {
            throw ApiConsumerNotFound::withId($command->consumerId);
        }

        $newKey = IntegrationKey::generate();
        $consumer->rotateIntegrationKey($newKey);
        $this->consumers->save($consumer);

        $this->auditLogger->log(
            new AuditEntry(
                action: 'integration.api_consumer_key_rotated',
                userId: $command->actorId,
                entity: 'ApiConsumer',
                entityId: $consumer->id()->value(),
            ),
        );

        return ApiConsumerResponse::fromApiConsumer($consumer, $newKey->plainValue());
    }
}
