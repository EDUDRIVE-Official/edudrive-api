<?php

declare(strict_types=1);

namespace Modules\FileStorage\Infrastructure\Storage;

use DateTimeImmutable;
use Illuminate\Support\Facades\Storage;
use Modules\FileStorage\Application\Contracts\FileStorage;
use RuntimeException;

final readonly class S3FileStorage implements FileStorage
{
    private const string DISK = 's3';

    public function store(string $storagePath, string $localTmpPath): void
    {
        $stream = fopen($localTmpPath, 'r');

        if ($stream === false) {
            throw new RuntimeException("No se pudo abrir el archivo temporal \"{$localTmpPath}\".");
        }

        try {
            Storage::disk(self::DISK)->put($storagePath, $stream);
        } finally {
            fclose($stream);
        }
    }

    public function delete(string $storagePath): void
    {
        Storage::disk(self::DISK)->delete($storagePath);
    }

    public function temporaryDownloadUrl(string $storagePath, DateTimeImmutable $expiresAt): string
    {
        return Storage::disk(self::DISK)->temporaryUrl($storagePath, $expiresAt);
    }
}
