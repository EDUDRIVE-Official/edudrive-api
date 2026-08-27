<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\Responses;

use DateTimeInterface;
use Modules\FileStorage\Domain\Aggregates\StoredFile;

final readonly class FileResponse
{
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $originalFilename,
        public string $mimeType,
        public int $sizeBytes,
        public string $scanStatus,
        public string $uploadedAt,
    ) {}

    public static function fromStoredFile(StoredFile $file): self
    {
        return new self(
            id: $file->id()->value(),
            ownerId: $file->ownerId(),
            originalFilename: $file->originalFilename(),
            mimeType: $file->mimeType(),
            sizeBytes: $file->sizeBytes(),
            scanStatus: $file->scanStatus()->value,
            uploadedAt: $file->uploadedAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->ownerId,
            'original_filename' => $this->originalFilename,
            'mime_type' => $this->mimeType,
            'size_bytes' => $this->sizeBytes,
            'scan_status' => $this->scanStatus,
            'uploaded_at' => $this->uploadedAt,
        ];
    }
}
