<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Support;

use Illuminate\Support\Str;
use Modules\Identity\Application\Services\UuidGenerator;

final class LaravelUuidGenerator implements UuidGenerator
{
    public function generate(): string
    {
        return (string) Str::uuid();
    }
}
