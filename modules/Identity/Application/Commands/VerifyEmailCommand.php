<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Commands;

final readonly class VerifyEmailCommand
{
    public function __construct(
        public string $email,
        public string $token,
    ) {}
}
