<?php

declare(strict_types=1);

namespace Modules\Backup\Application\Services;

interface DatabaseDumper
{
    public function dump(string $localPath): void;
}
