<?php

declare(strict_types=1);

namespace Modules\Integration\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Integration\Domain\Enums\ApiConsumerStatus;
use Modules\Integration\Domain\Exceptions\InvalidApiConsumerTransition;
use Modules\Integration\Domain\ValueObjects\ApiConsumerHistoryEntry;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;
use Modules\Integration\Domain\ValueObjects\IntegrationKey;

final class ApiConsumer
{
    /**
     * @param  list<string>  $scopes
     * @param  list<ApiConsumerHistoryEntry>  $history
     */
    private function __construct(
        private ApiConsumerId $id,
        private string $name,
        private array $scopes,
        private ApiConsumerStatus $status,
        private IntegrationKey $integrationKey,
        private ?DateTimeImmutable $expiresAt,
        private DateTimeImmutable $createdAt,
        private array $history,
    ) {}

    /** @param  list<string>  $scopes */
    public static function register(
        ApiConsumerId $id,
        string $name,
        array $scopes,
        IntegrationKey $integrationKey,
        ?DateTimeImmutable $expiresAt = null,
        ?DateTimeImmutable $createdAt = null,
    ): self {
        return new self(
            $id,
            $name,
            $scopes,
            ApiConsumerStatus::Active,
            $integrationKey,
            $expiresAt,
            $createdAt ?? new DateTimeImmutable('now'),
            [],
        );
    }

    /**
     * @param  list<string>  $scopes
     * @param  list<ApiConsumerHistoryEntry>  $history
     */
    public static function restore(
        ApiConsumerId $id,
        string $name,
        array $scopes,
        ApiConsumerStatus $status,
        IntegrationKey $integrationKey,
        ?DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
        array $history,
    ): self {
        return new self($id, $name, $scopes, $status, $integrationKey, $expiresAt, $createdAt, $history);
    }

    public function suspend(?string $reason, DateTimeImmutable $at): void
    {
        if ($this->status !== ApiConsumerStatus::Active) {
            throw InvalidApiConsumerTransition::create();
        }

        $this->transitionTo(ApiConsumerStatus::Suspended, $reason, $at);
    }

    public function reactivate(DateTimeImmutable $at): void
    {
        if ($this->status !== ApiConsumerStatus::Suspended) {
            throw InvalidApiConsumerTransition::create();
        }

        $this->transitionTo(ApiConsumerStatus::Active, null, $at);
    }

    public function revoke(?string $reason, DateTimeImmutable $at): void
    {
        if ($this->status === ApiConsumerStatus::Revoked) {
            throw InvalidApiConsumerTransition::create();
        }

        $this->transitionTo(ApiConsumerStatus::Revoked, $reason, $at);
    }

    public function rotateIntegrationKey(IntegrationKey $newKey): void
    {
        $this->integrationKey = $newKey;
    }

    public function isUsableAt(DateTimeImmutable $asOf): bool
    {
        if ($this->status !== ApiConsumerStatus::Active) {
            return false;
        }

        return $this->expiresAt === null || $this->expiresAt > $asOf;
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    public function id(): ApiConsumerId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return list<string> */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function status(): ApiConsumerStatus
    {
        return $this->status;
    }

    public function integrationKey(): IntegrationKey
    {
        return $this->integrationKey;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return list<ApiConsumerHistoryEntry> */
    public function history(): array
    {
        return $this->history;
    }

    private function transitionTo(ApiConsumerStatus $to, ?string $reason, DateTimeImmutable $at): void
    {
        $this->history[] = ApiConsumerHistoryEntry::statusChanged($this->status, $to, $at, $reason);
        $this->status = $to;
    }
}
