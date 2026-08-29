<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Responses;

use DateTimeInterface;
use Modules\AiGovernance\Domain\Aggregates\AiModel;

final readonly class AiModelResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $provider,
        public string $version,
        public ?string $ownerId,
        public ?string $useCase,
        public string $status,
        public ?string $knownRisks,
        public string $registeredAt,
    ) {}

    public static function fromModel(AiModel $model): self
    {
        return new self(
            id: $model->id()->value(),
            name: $model->name(),
            provider: $model->provider(),
            version: $model->version(),
            ownerId: $model->ownerId(),
            useCase: $model->useCase(),
            status: $model->status()->value,
            knownRisks: $model->knownRisks(),
            registeredAt: $model->registeredAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider' => $this->provider,
            'version' => $this->version,
            'owner_id' => $this->ownerId,
            'use_case' => $this->useCase,
            'status' => $this->status,
            'known_risks' => $this->knownRisks,
            'registered_at' => $this->registeredAt,
        ];
    }
}
