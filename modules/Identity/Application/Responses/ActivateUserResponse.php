<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Responses;

final readonly class ActivateUserResponse
{
    public function __construct(
        public string $userId,
        public string $status,
        public string $message,
    ) {}
}
