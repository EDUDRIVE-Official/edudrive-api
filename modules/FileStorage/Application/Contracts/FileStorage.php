<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\Contracts;

use DateTimeImmutable;

interface FileStorage
{
    public function store(string $storagePath, string $localTmpPath): void;

    public function delete(string $storagePath): void;

    public function temporaryDownloadUrl(string $storagePath, DateTimeImmutable $expiresAt): string;

    public function readToLocalFile(string $storagePath, string $localTmpPath): void;
}
