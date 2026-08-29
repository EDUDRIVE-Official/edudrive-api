<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Repositories;

use Modules\Identity\Domain\Entities\PasswordResetToken;
use Modules\Identity\Domain\ValueObjects\Email;

interface PasswordResetTokenRepository
{
    public function save(PasswordResetToken $token): void;

    public function findByEmail(Email $email): ?PasswordResetToken;

    public function deleteByEmail(Email $email): void;
}
