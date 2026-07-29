<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Exceptions;

use RuntimeException;

final class InvalidCredentials extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The provided credentials are invalid.');
    }
}
