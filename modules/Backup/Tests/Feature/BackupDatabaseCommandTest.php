<?php

declare(strict_types=1);

use Modules\Backup\Application\Services\DatabaseDumper;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Tests\TestCase;

final class FakeDatabaseDumperForBackupCommand implements DatabaseDumper
{
    public bool $called = false;

    public function dump(string $localPath): void
    {
        $this->called = true;
        file_put_contents($localPath, 'contenido-del-respaldo');
    }
}

final class FakeFileStorageForBackupCommand implements FileStorage
{
    /** @var array<string, string> */
    public array $stored = [];

    public function store(string $storagePath, string $localTmpPath): void
    {
        $contents = file_get_contents($localTmpPath);
        $this->stored[$storagePath] = $contents === false ? '' : $contents;
    }

    public function delete(string $storagePath): void
    {
        unset($this->stored[$storagePath]);
    }

    public function temporaryDownloadUrl(string $storagePath, DateTimeImmutable $expiresAt): string
    {
        return "https://minio.local/{$storagePath}";
    }

    public function readToLocalFile(string $storagePath, string $localTmpPath): void
    {
        file_put_contents($localTmpPath, $this->stored[$storagePath] ?? '');
    }
}

it('genera el respaldo y lo sube al almacenamiento con una ruta con marca de tiempo', function (): void {
    /** @var TestCase $this */
    $dumper = new FakeDatabaseDumperForBackupCommand;
    $fileStorage = new FakeFileStorageForBackupCommand;
    $this->app->instance(DatabaseDumper::class, $dumper);
    $this->app->instance(FileStorage::class, $fileStorage);

    $this->artisan('backup:database')->assertSuccessful();

    expect($dumper->called)->toBeTrue()
        ->and($fileStorage->stored)->toHaveCount(1);

    $storagePath = array_key_first($fileStorage->stored);
    expect($storagePath)->toStartWith('backups/postgres/')->toEndWith('.dump')
        ->and($fileStorage->stored[$storagePath])->toBe('contenido-del-respaldo');
});
