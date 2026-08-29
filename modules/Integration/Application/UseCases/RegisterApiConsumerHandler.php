<?php

declare(strict_types=1);

namespace Modules\Integration\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Integration\Application\Commands\RegisterApiConsumerCommand;
use Modules\Integration\Application\Exceptions\InvalidApiConsumerScope;
use Modules\Integration\Application\Responses\ApiConsumerResponse;
use Modules\Integration\Domain\Aggregates\ApiConsumer;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;
use Modules\Integration\Domain\ValueObjects\IntegrationKey;

final readonly class RegisterApiConsumerHandler
{
    public function __construct(
        private ApiConsumerRepository $consumers,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(RegisterApiConsumerCommand $command): ApiConsumerResponse
    {
        foreach ($command->scopes as $scope) {
            if (Permission::tryFrom($scope) === null) {
                throw InvalidApiConsumerScope::withValue($scope);
            }
        }

        $integrationKey = IntegrationKey::generate();
        $consumer = ApiConsumer::register(
            id: ApiConsumerId::fromString((string) Str::uuid()),
            name: $command->name,
            scopes: $command->scopes,
            integrationKey: $integrationKey,
            expiresAt: $command->expiresAt === null ? null : new DateTimeImmutable($command->expiresAt),
        );

        $this->consumers->save($consumer);

        $this->auditLogger->log(
            new AuditEntry(
                action: 'integration.api_consumer_registered',
                userId: $command->actorId,
                entity: 'ApiConsumer',
                entityId: $consumer->id()->value(),
                metadata: [
                    'name' => $command->name,
                    'scopes' => $command->scopes,
                ],
            ),
        );

        return ApiConsumerResponse::fromApiConsumer($consumer, $integrationKey->plainValue());
    }
}
