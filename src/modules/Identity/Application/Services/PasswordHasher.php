<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Services;

interface PasswordHasher
{
    public function hash(string $plainPassword): string;
}
