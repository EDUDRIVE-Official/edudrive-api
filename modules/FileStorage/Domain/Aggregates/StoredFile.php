<?php

declare(strict_types=1);

namespace Modules\FileStorage\Domain\Aggregates;

use DateTimeImmutable;
use Modules\FileStorage\Domain\Enums\FileScanStatus;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;

final class StoredFile
{
    private function __construct(
        private StoredFileId $id,
        private string $ownerId,
        private string $originalFilename,
        private string $mimeType,
        private int $sizeBytes,
        private string $storagePath,
        private FileScanStatus $scanStatus,
        private DateTimeImmutable $uploadedAt,
    ) {}

    public static function upload(
        StoredFileId $id,
        string $ownerId,
        string $originalFilename,
        string $mimeType,
        int $sizeBytes,
        string $storagePath,
        ?DateTimeImmutable $uploadedAt = null,
    ): self {
        return new self(
            $id,
            $ownerId,
            $originalFilename,
            $mimeType,
            $sizeBytes,
            $storagePath,
            FileScanStatus::Pending,
            $uploadedAt ?? new DateTimeImmutable('now'),
        );
    }

    public static function restore(
        StoredFileId $id,
        string $ownerId,
        string $originalFilename,
        string $mimeType,
        int $sizeBytes,
        string $storagePath,
        FileScanStatus $scanStatus,
        DateTimeImmutable $uploadedAt,
    ): self {
        return new self($id, $ownerId, $originalFilename, $mimeType, $sizeBytes, $storagePath, $scanStatus, $uploadedAt);
    }

    public function setScanStatus(FileScanStatus $status): void
    {
        $this->scanStatus = $status;
    }

    public function isOwnedBy(string $userId): bool
    {
        return $this->ownerId === $userId;
    }

    public function id(): StoredFileId
    {
        return $this->id;
    }

    public function ownerId(): string
    {
        return $this->ownerId;
    }

    public function originalFilename(): string
    {
        return $this->originalFilename;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function sizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function storagePath(): string
    {
        return $this->storagePath;
    }

    public function scanStatus(): FileScanStatus
    {
        return $this->scanStatus;
    }

    public function uploadedAt(): DateTimeImmutable
    {
        return $this->uploadedAt;
    }
}
