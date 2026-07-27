<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Responses;

final readonly class AuthenticatedUserResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $status,
    ) {}
}
