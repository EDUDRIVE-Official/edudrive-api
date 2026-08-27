<?php

declare(strict_types=1);

namespace Modules\FileStorage\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\FileStorage\Domain\Aggregates\StoredFile;
use Modules\FileStorage\Domain\Enums\FileScanStatus;
use Modules\FileStorage\Domain\Repositories\FileRepository;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;
use Modules\FileStorage\Infrastructure\Persistence\Eloquent\Models\StoredFileModel;

final readonly class EloquentFileRepository implements FileRepository
{
    public function save(StoredFile $file): void
    {
        StoredFileModel::query()->updateOrCreate(
            ['id' => $file->id()->value()],
            [
                'owner_id' => $file->ownerId(),
                'original_filename' => $file->originalFilename(),
                'mime_type' => $file->mimeType(),
                'size_bytes' => $file->sizeBytes(),
                'storage_path' => $file->storagePath(),
                'scan_status' => $file->scanStatus()->value,
                'uploaded_at' => $file->uploadedAt(),
            ],
        );
    }

    public function findById(StoredFileId $id): ?StoredFile
    {
        $model = StoredFileModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<StoredFile> */
    public function allForOwner(string $ownerId): array
    {
        return array_values(
            StoredFileModel::query()
                ->where('owner_id', $ownerId)
                ->orderBy('uploaded_at')
                ->get()
                ->map(fn (StoredFileModel $model): StoredFile => $this->toDomain($model))
                ->all(),
        );
    }

    public function totalBytesForOwner(string $ownerId): int
    {
        return (int) StoredFileModel::query()
            ->where('owner_id', $ownerId)
            ->sum('size_bytes');
    }

    public function delete(StoredFileId $id): void
    {
        StoredFileModel::query()->where('id', $id->value())->delete();
    }

    private function toDomain(StoredFileModel $model): StoredFile
    {
        return StoredFile::restore(
            id: StoredFileId::fromString((string) $model->getAttribute('id')),
            ownerId: (string) $model->getAttribute('owner_id'),
            originalFilename: (string) $model->getAttribute('original_filename'),
            mimeType: (string) $model->getAttribute('mime_type'),
            sizeBytes: (int) $model->getAttribute('size_bytes'),
            storagePath: (string) $model->getAttribute('storage_path'),
            scanStatus: FileScanStatus::from((string) $model->getAttribute('scan_status')),
            uploadedAt: new DateTimeImmutable((string) $model->getAttribute('uploaded_at')),
        );
    }
}
