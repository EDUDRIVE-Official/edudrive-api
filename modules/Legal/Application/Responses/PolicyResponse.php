<?php

declare(strict_types=1);

namespace Modules\Legal\Application\Responses;

use DateTimeInterface;
use Modules\Legal\Domain\Aggregates\ConsentPolicy;

final readonly class PolicyResponse
{
    public function __construct(
        public string $key,
        public int $version,
        public string $effectiveAt,
    ) {}

    public static function fromConsentPolicy(ConsentPolicy $policy): self
    {
        return new self(
            key: $policy->key()->value(),
            version: $policy->version(),
            effectiveAt: $policy->effectiveAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array{key: string, version: int, effective_at: string} */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'version' => $this->version,
            'effective_at' => $this->effectiveAt,
        ];
    }
}
