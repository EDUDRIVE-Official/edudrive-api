<?php

declare(strict_types=1);

namespace Modules\Foundation\Infrastructure\Export;

use DateTimeImmutable;
use Modules\FileStorage\Application\Contracts\FileStorage;
use RuntimeException;

final readonly class ExportFileWriter
{
    private const int URL_LIFETIME_MINUTES = 15;

    public function __construct(private FileStorage $fileStorage) {}

    public function write(string $storagePath, string $contents): ExportedFile
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'export_');
        if ($tmpPath === false) {
            throw new RuntimeException('No se pudo crear un archivo temporal para la exportación.');
        }

        file_put_contents($tmpPath, $contents);

        try {
            $this->fileStorage->store($storagePath, $tmpPath);
        } finally {
            unlink($tmpPath);
        }

        $expiresAt = new DateTimeImmutable('+'.self::URL_LIFETIME_MINUTES.' minutes');

        return new ExportedFile(
            url: $this->fileStorage->temporaryDownloadUrl($storagePath, $expiresAt),
            expiresAt: $expiresAt,
        );
    }
}
