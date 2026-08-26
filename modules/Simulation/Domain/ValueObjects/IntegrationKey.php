<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\ValueObjects;

final readonly class IntegrationKey
{
    private function __construct(
        private ?string $plainValue,
        private string $hash,
    ) {}

    public static function generate(): self
    {
        $plainValue = bin2hex(random_bytes(32));

        return new self($plainValue, self::hashValue($plainValue));
    }

    public static function fromHash(string $hash): self
    {
        return new self(null, $hash);
    }

    public function plainValue(): ?string
    {
        return $this->plainValue;
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public function matches(string $candidate): bool
    {
        return hash_equals($this->hash, self::hashValue($candidate));
    }

    private static function hashValue(string $plainValue): string
    {
        return hash('sha256', $plainValue);
    }
}
