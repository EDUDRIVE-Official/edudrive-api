<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Admin\Domain\Aggregates\SystemSetting;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;
use Modules\FileStorage\Application\Commands\DeleteFileCommand;
use Modules\FileStorage\Application\Commands\SetFileScanStatusCommand;
use Modules\FileStorage\Application\Commands\UploadFileCommand;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Modules\FileStorage\Application\Exceptions\FileNotFound;
use Modules\FileStorage\Application\Exceptions\FileQuotaExceeded;
use Modules\FileStorage\Application\Queries\GetFileDownloadUrlQuery;
use Modules\FileStorage\Application\Queries\GetFileQuery;
use Modules\FileStorage\Application\Queries\GetMyFilesQuery;
use Modules\FileStorage\Application\Responses\FileResponse;
use Modules\FileStorage\Application\UseCases\DeleteFileHandler;
use Modules\FileStorage\Application\UseCases\GetFileDownloadUrlHandler;
use Modules\FileStorage\Application\UseCases\GetFileHandler;
use Modules\FileStorage\Application\UseCases\GetMyFilesHandler;
use Modules\FileStorage\Application\UseCases\SetFileScanStatusHandler;
use Modules\FileStorage\Application\UseCases\UploadFileHandler;
use Modules\FileStorage\Domain\Aggregates\StoredFile;
use Modules\FileStorage\Domain\Repositories\FileRepository;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;

final class InMemoryFileRepository implements FileRepository
{
    /** @var array<string, StoredFile> */
    public array $items = [];

    public function save(StoredFile $file): void
    {
        $this->items[$file->id()->value()] = $file;
    }

    public function findById(StoredFileId $id): ?StoredFile
    {
        return $this->items[$id->value()] ?? null;
    }

    public function allForOwner(string $ownerId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (StoredFile $file): bool => $file->isOwnedBy($ownerId),
        ));
    }

    public function totalBytesForOwner(string $ownerId): int
    {
        return array_sum(array_map(
            static fn (StoredFile $file): int => $file->sizeBytes(),
            $this->allForOwner($ownerId),
        ));
    }

    public function delete(StoredFileId $id): void
    {
        unset($this->items[$id->value()]);
    }
}

final class FakeFileStorage implements FileStorage
{
    /** @var list<string> */
    public array $stored = [];

    /** @var list<string> */
    public array $deleted = [];

    public function store(string $storagePath, string $localTmpPath): void
    {
        $this->stored[] = $storagePath;
    }

    public function delete(string $storagePath): void
    {
        $this->deleted[] = $storagePath;
    }

    public function temporaryDownloadUrl(string $storagePath, DateTimeImmutable $expiresAt): string
    {
        return "https://minio.local/{$storagePath}?expires={$expiresAt->getTimestamp()}";
    }
}

final class InMemorySystemSettingRepositoryForFiles implements SystemSettingRepository
{
    /** @var array<string, SystemSetting> */
    public array $items = [];

    public function save(SystemSetting $setting): void
    {
        $this->items[$setting->key()->value()] = $setting;
    }

    public function findByKey(SystemSettingKey $key): ?SystemSetting
    {
        return $this->items[$key->value()] ?? null;
    }

    public function all(): array
    {
        return array_values($this->items);
    }
}

function persistedFileFor(InMemoryFileRepository $repository, ?string $ownerId = null, int $sizeBytes = 1024): StoredFile
{
    $file = StoredFile::upload(
        id: StoredFileId::fromString((string) Str::uuid()),
        ownerId: $ownerId ?? (string) Str::uuid(),
        originalFilename: 'informe.pdf',
        mimeType: 'application/pdf',
        sizeBytes: $sizeBytes,
        storagePath: sprintf('files/owner/%s/informe.pdf', (string) Str::uuid()),
    );
    $repository->save($file);

    return $file;
}

it('sube un archivo dentro de la cuota y lo deja en estado pending', function (): void {
    $files = new InMemoryFileRepository;
    $storage = new FakeFileStorage;
    $settings = new InMemorySystemSettingRepositoryForFiles;
    $ownerId = (string) Str::uuid();

    $response = (new UploadFileHandler($files, $storage, $settings))->handle(new UploadFileCommand(
        ownerId: $ownerId,
        originalFilename: 'informe.pdf',
        mimeType: 'application/pdf',
        sizeBytes: 2048,
        localTmpPath: '/tmp/informe.pdf',
    ));

    expect($response)->toBeInstanceOf(FileResponse::class)
        ->and($response->ownerId)->toBe($ownerId)
        ->and($response->scanStatus)->toBe('pending')
        ->and($storage->stored)->toHaveCount(1);
});

it('rechaza subir un archivo que supera la cuota configurada sin escribir en el almacenamiento', function (): void {
    $files = new InMemoryFileRepository;
    $storage = new FakeFileStorage;
    $settings = new InMemorySystemSettingRepositoryForFiles;
    $ownerId = (string) Str::uuid();
    $settings->save(SystemSetting::set(SystemSettingKey::fromString('file_storage_quota_bytes'), '1000'));
    persistedFileFor($files, $ownerId, 900);

    expect(fn () => (new UploadFileHandler($files, $storage, $settings))->handle(new UploadFileCommand(
        ownerId: $ownerId,
        originalFilename: 'informe.pdf',
        mimeType: 'application/pdf',
        sizeBytes: 500,
        localTmpPath: '/tmp/informe.pdf',
    )))->toThrow(FileQuotaExceeded::class);

    expect($storage->stored)->toBeEmpty();
});

it('usa una cuota por defecto cuando no hay un ajuste configurado', function (): void {
    $files = new InMemoryFileRepository;
    $storage = new FakeFileStorage;
    $settings = new InMemorySystemSettingRepositoryForFiles;

    $response = (new UploadFileHandler($files, $storage, $settings))->handle(new UploadFileCommand(
        ownerId: (string) Str::uuid(),
        originalFilename: 'informe.pdf',
        mimeType: 'application/pdf',
        sizeBytes: 2048,
        localTmpPath: '/tmp/informe.pdf',
    ));

    expect($response)->toBeInstanceOf(FileResponse::class);
});

it('devuelve el archivo al dueno o a un tercero con permiso ampliado', function (): void {
    $files = new InMemoryFileRepository;
    $file = persistedFileFor($files);

    $ownResponse = (new GetFileHandler($files))->handle(new GetFileQuery(
        fileId: $file->id()->value(),
        requestingUserId: $file->ownerId(),
        canViewOthers: false,
    ));
    expect($ownResponse->id)->toBe($file->id()->value());

    $othersResponse = (new GetFileHandler($files))->handle(new GetFileQuery(
        fileId: $file->id()->value(),
        requestingUserId: (string) Str::uuid(),
        canViewOthers: true,
    ));
    expect($othersResponse->id)->toBe($file->id()->value());
});

it('rechaza consultar el archivo de un tercero sin permiso ampliado como si no existiera', function (): void {
    $files = new InMemoryFileRepository;
    $file = persistedFileFor($files);

    expect(fn () => (new GetFileHandler($files))->handle(new GetFileQuery(
        fileId: $file->id()->value(),
        requestingUserId: (string) Str::uuid(),
        canViewOthers: false,
    )))->toThrow(FileNotFound::class);
});

it('rechaza consultar un archivo inexistente', function (): void {
    $files = new InMemoryFileRepository;

    expect(fn () => (new GetFileHandler($files))->handle(new GetFileQuery(
        fileId: (string) Str::uuid(),
        requestingUserId: (string) Str::uuid(),
        canViewOthers: true,
    )))->toThrow(FileNotFound::class);
});

it('lista unicamente los archivos propios', function (): void {
    $files = new InMemoryFileRepository;
    $ownerId = (string) Str::uuid();
    persistedFileFor($files, $ownerId);
    persistedFileFor($files, $ownerId);
    persistedFileFor($files);

    $response = (new GetMyFilesHandler($files))->handle(new GetMyFilesQuery($ownerId));

    expect($response)->toHaveCount(2);
});

it('genera una url temporal de descarga para el dueno del archivo', function (): void {
    $files = new InMemoryFileRepository;
    $storage = new FakeFileStorage;
    $file = persistedFileFor($files);

    $response = (new GetFileDownloadUrlHandler($files, $storage))->handle(new GetFileDownloadUrlQuery(
        fileId: $file->id()->value(),
        requestingUserId: $file->ownerId(),
        canViewOthers: false,
    ));

    expect($response->url)->toContain($file->storagePath());
});

it('rechaza generar una url de descarga para el archivo de un tercero sin permiso ampliado', function (): void {
    $files = new InMemoryFileRepository;
    $storage = new FakeFileStorage;
    $file = persistedFileFor($files);

    expect(fn () => (new GetFileDownloadUrlHandler($files, $storage))->handle(new GetFileDownloadUrlQuery(
        fileId: $file->id()->value(),
        requestingUserId: (string) Str::uuid(),
        canViewOthers: false,
    )))->toThrow(FileNotFound::class);
});

it('elimina un archivo propio del repositorio y del almacenamiento', function (): void {
    $files = new InMemoryFileRepository;
    $storage = new FakeFileStorage;
    $file = persistedFileFor($files);

    (new DeleteFileHandler($files, $storage))->handle(new DeleteFileCommand(
        fileId: $file->id()->value(),
        requestingUserId: $file->ownerId(),
        canManageOthers: false,
    ));

    expect($files->findById($file->id()))->toBeNull()
        ->and($storage->deleted)->toContain($file->storagePath());
});

it('permite eliminar el archivo de un tercero con permiso de gestion ampliado', function (): void {
    $files = new InMemoryFileRepository;
    $storage = new FakeFileStorage;
    $file = persistedFileFor($files);

    (new DeleteFileHandler($files, $storage))->handle(new DeleteFileCommand(
        fileId: $file->id()->value(),
        requestingUserId: (string) Str::uuid(),
        canManageOthers: true,
    ));

    expect($files->findById($file->id()))->toBeNull();
});

it('rechaza eliminar el archivo de un tercero sin permiso de gestion ampliado', function (): void {
    $files = new InMemoryFileRepository;
    $storage = new FakeFileStorage;
    $file = persistedFileFor($files);

    expect(fn () => (new DeleteFileHandler($files, $storage))->handle(new DeleteFileCommand(
        fileId: $file->id()->value(),
        requestingUserId: (string) Str::uuid(),
        canManageOthers: false,
    )))->toThrow(FileNotFound::class);

    expect($files->findById($file->id()))->not->toBeNull();
});

it('actualiza el estado de escaneo de un archivo existente', function (): void {
    $files = new InMemoryFileRepository;
    $file = persistedFileFor($files);

    $response = (new SetFileScanStatusHandler($files))->handle(new SetFileScanStatusCommand(
        fileId: $file->id()->value(),
        scanStatus: 'clean',
    ));

    expect($response->scanStatus)->toBe('clean');
});

it('rechaza actualizar el estado de escaneo de un archivo inexistente', function (): void {
    $files = new InMemoryFileRepository;

    expect(fn () => (new SetFileScanStatusHandler($files))->handle(new SetFileScanStatusCommand(
        fileId: (string) Str::uuid(),
        scanStatus: 'clean',
    )))->toThrow(FileNotFound::class);
});
