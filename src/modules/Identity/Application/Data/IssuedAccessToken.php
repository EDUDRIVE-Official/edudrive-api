<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Data;

final readonly class IssuedAccessToken
{
    public function __construct(
        public string $plainTextToken,
        public string $tokenType = 'Bearer',
    ) {}
}
