<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Responses;

final readonly class LoginUserResponse
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $email,
        public string $status,
        public string $accessToken,
        public string $tokenType,
    ) {}
}
