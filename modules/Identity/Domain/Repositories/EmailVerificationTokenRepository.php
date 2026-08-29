<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Repositories;

use Modules\Identity\Domain\Entities\EmailVerificationToken;
use Modules\Identity\Domain\ValueObjects\Email;

interface EmailVerificationTokenRepository
{
    public function save(EmailVerificationToken $token): void;

    public function findByEmail(Email $email): ?EmailVerificationToken;

    public function deleteByEmail(Email $email): void;
}
