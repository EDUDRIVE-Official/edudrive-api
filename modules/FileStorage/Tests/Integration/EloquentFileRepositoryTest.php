<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\FileStorage\Domain\Aggregates\StoredFile;
use Modules\FileStorage\Domain\Enums\FileScanStatus;
use Modules\FileStorage\Domain\Repositories\FileRepository;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function persistedFileOwnerId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Propietario de archivos',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function newPersistableStoredFile(string $ownerId, int $sizeBytes = 2048): StoredFile
{
    return StoredFile::upload(
        id: StoredFileId::fromString((string) Str::uuid()),
        ownerId: $ownerId,
        originalFilename: 'informe.pdf',
        mimeType: 'application/pdf',
        sizeBytes: $sizeBytes,
        storagePath: sprintf('files/%s/informe.pdf', (string) Str::uuid()),
        uploadedAt: new DateTimeImmutable('2026-08-27T10:00:00+00:00'),
    );
}

it('guarda y recupera un archivo por identificador', function (): void {
    $ownerId = persistedFileOwnerId();
    $file = newPersistableStoredFile($ownerId);

    app(FileRepository::class)->save($file);
    $found = app(FileRepository::class)->findById($file->id());

    expect($found)->not->toBeNull()
        ->and($found?->originalFilename())->toBe('informe.pdf')
        ->and($found?->sizeBytes())->toBe(2048)
        ->and($found?->scanStatus())->toBe(FileScanStatus::Pending);
});

it('lista los archivos de un propietario', function (): void {
    $ownerId = persistedFileOwnerId();
    $otherOwnerId = persistedFileOwnerId();
    $repository = app(FileRepository::class);

    $repository->save(newPersistableStoredFile($ownerId));
    $repository->save(newPersistableStoredFile($ownerId));
    $repository->save(newPersistableStoredFile($otherOwnerId));

    expect($repository->allForOwner($ownerId))->toHaveCount(2);
});

it('suma el tamano total de los archivos de un propietario', function (): void {
    $ownerId = persistedFileOwnerId();
    $repository = app(FileRepository::class);

    $repository->save(newPersistableStoredFile($ownerId, 1000));
    $repository->save(newPersistableStoredFile($ownerId, 2500));

    expect($repository->totalBytesForOwner($ownerId))->toBe(3500);
});

it('elimina un archivo existente', function (): void {
    $ownerId = persistedFileOwnerId();
    $file = newPersistableStoredFile($ownerId);
    $repository = app(FileRepository::class);
    $repository->save($file);

    $repository->delete($file->id());

    expect($repository->findById($file->id()))->toBeNull();
});

it('no encuentra un archivo inexistente', function (): void {
    expect(app(FileRepository::class)->findById(StoredFileId::fromString((string) Str::uuid())))->toBeNull();
});
