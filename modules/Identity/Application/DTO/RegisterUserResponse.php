<?php

declare(strict_types=1);

namespace Modules\Identity\Application\DTO;

final readonly class RegisterUserResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $status,
    ) {}
}
