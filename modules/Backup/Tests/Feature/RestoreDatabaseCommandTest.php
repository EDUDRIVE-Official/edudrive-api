<?php

declare(strict_types=1);

use Modules\Backup\Application\Services\DatabaseRestorer;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Tests\TestCase;

final class FakeDatabaseRestorerForRestoreCommand implements DatabaseRestorer
{
    public ?string $restoredContent = null;

    public function restore(string $localPath): void
    {
        $contents = file_get_contents($localPath);
        $this->restoredContent = $contents === false ? null : $contents;
    }
}

final class FakeFileStorageForRestoreCommand implements FileStorage
{
    /** @var array<string, string> */
    public array $stored = [];

    public function store(string $storagePath, string $localTmpPath): void
    {
        $contents = file_get_contents($localTmpPath);
        $this->stored[$storagePath] = $contents === false ? '' : $contents;
    }

    public function delete(string $storagePath): void {}

    public function temporaryDownloadUrl(string $storagePath, DateTimeImmutable $expiresAt): string
    {
        return "https://minio.local/{$storagePath}";
    }

    public function readToLocalFile(string $storagePath, string $localTmpPath): void
    {
        file_put_contents($localTmpPath, $this->stored[$storagePath] ?? '');
    }
}

it('restaura desde el respaldo indicado tras confirmar', function (): void {
    /** @var TestCase $this */
    $restorer = new FakeDatabaseRestorerForRestoreCommand;
    $fileStorage = new FakeFileStorageForRestoreCommand;
    $fileStorage->stored['backups/postgres/2026-08-29_120000.dump'] = 'contenido-del-respaldo';
    $this->app->instance(DatabaseRestorer::class, $restorer);
    $this->app->instance(FileStorage::class, $fileStorage);

    $this->artisan('backup:restore', ['path' => 'backups/postgres/2026-08-29_120000.dump', '--force' => true])
        ->assertSuccessful();

    expect($restorer->restoredContent)->toBe('contenido-del-respaldo');
});

it('cancela la restauracion cuando no se confirma', function (): void {
    /** @var TestCase $this */
    $restorer = new FakeDatabaseRestorerForRestoreCommand;
    $fileStorage = new FakeFileStorageForRestoreCommand;
    $fileStorage->stored['backups/postgres/2026-08-29_120000.dump'] = 'contenido-del-respaldo';
    $this->app->instance(DatabaseRestorer::class, $restorer);
    $this->app->instance(FileStorage::class, $fileStorage);

    $this->artisan('backup:restore', ['path' => 'backups/postgres/2026-08-29_120000.dump'])
        ->expectsConfirmation('Esto reemplazara el esquema actual de la base de datos con el respaldo "backups/postgres/2026-08-29_120000.dump". ¿Continuar?', 'no')
        ->assertSuccessful();

    expect($restorer->restoredContent)->toBeNull();
});
