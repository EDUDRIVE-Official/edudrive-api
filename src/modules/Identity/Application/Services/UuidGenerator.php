<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Services;

interface UuidGenerator
{
    public function generate(): string;
}
