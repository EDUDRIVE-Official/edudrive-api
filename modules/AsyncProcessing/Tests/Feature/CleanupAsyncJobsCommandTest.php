<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Tests\TestCase;

final class FakeFileStorageForCleanupCommand implements FileStorage
{
    /** @var list<string> */
    public array $deleted = [];

    public function store(string $storagePath, string $localTmpPath): void {}

    public function delete(string $storagePath): void
    {
        $this->deleted[] = $storagePath;
    }

    public function temporaryDownloadUrl(string $storagePath, DateTimeImmutable $expiresAt): string
    {
        return "https://minio.local/{$storagePath}";
    }

    public function readToLocalFile(string $storagePath, string $localTmpPath): void
    {
        file_put_contents($localTmpPath, '');
    }
}

it('borra el archivo de una exportacion completada vencida y purga el trabajo', function (): void {
    /** @var TestCase $this */
    $fileStorage = new FakeFileStorageForCleanupCommand;
    $this->app->instance(FileStorage::class, $fileStorage);

    $jobs = app(AsyncJobRepository::class);
    $expired = AsyncJob::request(AsyncJobId::fromString((string) Str::uuid()), 'export.enrollments', (string) Str::uuid());
    $expired->complete(['url' => 'https://x.test/f.csv', 'storage_path' => 'exports/enrollments/f.csv', 'format' => 'csv', 'row_count' => 1], new DateTimeImmutable('-25 hours'));
    $jobs->save($expired);

    $this->artisan('async-processing:cleanup')->assertSuccessful();

    expect($fileStorage->deleted)->toBe(['exports/enrollments/f.csv'])
        ->and($jobs->findById($expired->id()))->toBeNull();
});

it('no borra ningun archivo para un import purgado, solo el registro', function (): void {
    /** @var TestCase $this */
    $fileStorage = new FakeFileStorageForCleanupCommand;
    $this->app->instance(FileStorage::class, $fileStorage);

    $jobs = app(AsyncJobRepository::class);
    $expired = AsyncJob::request(AsyncJobId::fromString((string) Str::uuid()), 'import.users', (string) Str::uuid());
    $expired->complete(['total' => 1, 'created' => 1, 'failed' => 0, 'results' => []], new DateTimeImmutable('-25 hours'));
    $jobs->save($expired);

    $this->artisan('async-processing:cleanup')->assertSuccessful();

    expect($fileStorage->deleted)->toBeEmpty()
        ->and($jobs->findById($expired->id()))->toBeNull();
});

it('conserva los trabajos dentro del periodo de retencion', function (): void {
    /** @var TestCase $this */
    $this->app->instance(FileStorage::class, new FakeFileStorageForCleanupCommand);

    $jobs = app(AsyncJobRepository::class);
    $recent = AsyncJob::request(AsyncJobId::fromString((string) Str::uuid()), 'export.courses', (string) Str::uuid());
    $recent->complete(['storage_path' => 'exports/courses/f.csv'], new DateTimeImmutable('-1 hour'));
    $jobs->save($recent);

    $this->artisan('async-processing:cleanup')->assertSuccessful();

    expect($jobs->findById($recent->id()))->not->toBeNull();
});
