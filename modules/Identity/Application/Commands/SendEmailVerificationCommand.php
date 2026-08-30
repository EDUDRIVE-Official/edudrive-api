<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Commands;

final readonly class SendEmailVerificationCommand
{
    public function __construct(
        public string $email,
    ) {}
}
