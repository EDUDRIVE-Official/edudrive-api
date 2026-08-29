<?php

declare(strict_types=1);

namespace Modules\Identity\Application\DTO;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $dateOfBirth = null,
    ) {}
}
