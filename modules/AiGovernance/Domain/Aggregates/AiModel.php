<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Aggregates;

use DateTimeImmutable;
use Modules\AiGovernance\Domain\Enums\AiModelStatus;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiModelTransition;
use Modules\AiGovernance\Domain\ValueObjects\AiModelId;

final class AiModel
{
    private function __construct(
        private AiModelId $id,
        private string $name,
        private string $provider,
        private string $version,
        private ?string $ownerId,
        private ?string $useCase,
        private AiModelStatus $status,
        private ?string $knownRisks,
        private DateTimeImmutable $registeredAt,
    ) {}

    public static function register(
        AiModelId $id,
        string $name,
        string $provider,
        string $version,
        ?string $ownerId,
        ?string $useCase,
        ?string $knownRisks = null,
        ?DateTimeImmutable $registeredAt = null,
    ): self {
        return new self(
            $id,
            $name,
            $provider,
            $version,
            $ownerId,
            $useCase,
            AiModelStatus::Registered,
            $knownRisks,
            $registeredAt ?? new DateTimeImmutable('now'),
        );
    }

    public static function restore(
        AiModelId $id,
        string $name,
        string $provider,
        string $version,
        ?string $ownerId,
        ?string $useCase,
        AiModelStatus $status,
        ?string $knownRisks,
        DateTimeImmutable $registeredAt,
    ): self {
        return new self($id, $name, $provider, $version, $ownerId, $useCase, $status, $knownRisks, $registeredAt);
    }

    public function approve(): void
    {
        if ($this->status !== AiModelStatus::Registered) {
            throw InvalidAiModelTransition::create();
        }

        $this->status = AiModelStatus::Approved;
    }

    public function deprecate(): void
    {
        if ($this->status !== AiModelStatus::Approved) {
            throw InvalidAiModelTransition::create();
        }

        $this->status = AiModelStatus::Deprecated;
    }

    public function retire(): void
    {
        if ($this->status === AiModelStatus::Retired) {
            throw InvalidAiModelTransition::create();
        }

        $this->status = AiModelStatus::Retired;
    }

    public function id(): AiModelId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function ownerId(): ?string
    {
        return $this->ownerId;
    }

    public function useCase(): ?string
    {
        return $this->useCase;
    }

    public function status(): AiModelStatus
    {
        return $this->status;
    }

    public function knownRisks(): ?string
    {
        return $this->knownRisks;
    }

    public function registeredAt(): DateTimeImmutable
    {
        return $this->registeredAt;
    }
}
