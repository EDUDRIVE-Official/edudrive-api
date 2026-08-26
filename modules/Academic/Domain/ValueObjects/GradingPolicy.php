<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

final readonly class GradingPolicy
{
    public function __construct(
        private bool $allowPartialCredit,
        private bool $applyPenalties,
    ) {}

    public function allowPartialCredit(): bool
    {
        return $this->allowPartialCredit;
    }

    public function applyPenalties(): bool
    {
        return $this->applyPenalties;
    }

    /** @return array{allow_partial_credit: bool, apply_penalties: bool} */
    public function toArray(): array
    {
        return [
            'allow_partial_credit' => $this->allowPartialCredit,
            'apply_penalties' => $this->applyPenalties,
        ];
    }
}
