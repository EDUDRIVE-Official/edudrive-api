<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Commands;

final readonly class LoginUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public string $tokenName,
    ) {}
}
