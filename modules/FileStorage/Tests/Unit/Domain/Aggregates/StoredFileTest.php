<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\FileStorage\Domain\Aggregates\StoredFile;
use Modules\FileStorage\Domain\Enums\FileScanStatus;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;

function newStoredFile(?string $ownerId = null): StoredFile
{
    return StoredFile::upload(
        id: StoredFileId::fromString((string) Str::uuid()),
        ownerId: $ownerId ?? (string) Str::uuid(),
        originalFilename: 'informe.pdf',
        mimeType: 'application/pdf',
        sizeBytes: 2048,
        storagePath: 'files/example/informe.pdf',
        uploadedAt: new DateTimeImmutable('2026-08-27T10:00:00+00:00'),
    );
}

it('se sube en estado de escaneo pendiente', function (): void {
    $file = newStoredFile();

    expect($file->scanStatus())->toBe(FileScanStatus::Pending)
        ->and($file->originalFilename())->toBe('informe.pdf')
        ->and($file->mimeType())->toBe('application/pdf')
        ->and($file->sizeBytes())->toBe(2048);
});

it('confirma la pertenencia del archivo a su propietario', function (): void {
    $ownerId = (string) Str::uuid();
    $file = newStoredFile($ownerId);

    expect($file->isOwnedBy($ownerId))->toBeTrue()
        ->and($file->isOwnedBy((string) Str::uuid()))->toBeFalse();
});

it('permite actualizar el estado de escaneo', function (): void {
    $file = newStoredFile();

    $file->setScanStatus(FileScanStatus::Clean);

    expect($file->scanStatus())->toBe(FileScanStatus::Clean);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = StoredFileId::fromString((string) Str::uuid());
    $ownerId = (string) Str::uuid();
    $uploadedAt = new DateTimeImmutable('2026-08-27T10:00:00+00:00');

    $file = StoredFile::restore(
        id: $id,
        ownerId: $ownerId,
        originalFilename: 'informe.pdf',
        mimeType: 'application/pdf',
        sizeBytes: 2048,
        storagePath: 'files/example/informe.pdf',
        scanStatus: FileScanStatus::Infected,
        uploadedAt: $uploadedAt,
    );

    expect($file->id()->equals($id))->toBeTrue()
        ->and($file->ownerId())->toBe($ownerId)
        ->and($file->scanStatus())->toBe(FileScanStatus::Infected)
        ->and($file->uploadedAt())->toBe($uploadedAt);
});
