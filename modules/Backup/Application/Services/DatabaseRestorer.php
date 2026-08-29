<?php

declare(strict_types=1);

namespace Modules\Backup\Application\Services;

interface DatabaseRestorer
{
    public function restore(string $localPath): void;
}
