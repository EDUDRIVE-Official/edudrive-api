<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class CourseCode
{
    public function __construct(
        private string $value
    ) {
        if (! preg_match('/^[A-Z0-9-]{2,20}$/', $value)) {
            throw new InvalidArgumentException(
                'Invalid course code.'
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}