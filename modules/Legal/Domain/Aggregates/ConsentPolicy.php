<?php

declare(strict_types=1);

namespace Modules\Legal\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

final class ConsentPolicy
{
    private function __construct(
        private string $id,
        private PolicyKey $key,
        private int $version,
        private DateTimeImmutable $effectiveAt,
    ) {}

    public static function publish(
        string $id,
        PolicyKey $key,
        int $version,
        ?DateTimeImmutable $effectiveAt = null,
    ): self {
        return new self($id, $key, $version, $effectiveAt ?? new DateTimeImmutable('now'));
    }

    public static function restore(
        string $id,
        PolicyKey $key,
        int $version,
        DateTimeImmutable $effectiveAt,
    ): self {
        return new self($id, $key, $version, $effectiveAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function key(): PolicyKey
    {
        return $this->key;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function effectiveAt(): DateTimeImmutable
    {
        return $this->effectiveAt;
    }
}
