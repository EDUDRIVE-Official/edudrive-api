<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Security;

use Illuminate\Support\Facades\Hash;
use Modules\Identity\Application\Services\PasswordHasher;

final class LaravelPasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return Hash::make($plainPassword);
    }

    public function verify(
        string $plainPassword,
        string $hashedPassword,
    ): bool {
        return Hash::check(
            $plainPassword,
            $hashedPassword,
        );
    }
}
