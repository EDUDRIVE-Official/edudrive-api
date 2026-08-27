<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;
use Modules\FileStorage\Application\Commands\UploadFileCommand;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Modules\FileStorage\Application\Exceptions\FileQuotaExceeded;
use Modules\FileStorage\Application\Responses\FileResponse;
use Modules\FileStorage\Domain\Aggregates\StoredFile;
use Modules\FileStorage\Domain\Repositories\FileRepository;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;

final readonly class UploadFileHandler
{
    private const int DEFAULT_QUOTA_BYTES = 104_857_600;

    public function __construct(
        private FileRepository $files,
        private FileStorage $storage,
        private SystemSettingRepository $settings,
    ) {}

    public function handle(UploadFileCommand $command): FileResponse
    {
        $quotaLimitBytes = $this->quotaLimitBytes();
        $currentUsageBytes = $this->files->totalBytesForOwner($command->ownerId);

        if ($currentUsageBytes + $command->sizeBytes > $quotaLimitBytes) {
            throw FileQuotaExceeded::forOwner($command->ownerId, $quotaLimitBytes);
        }

        $id = StoredFileId::fromString((string) Str::uuid());
        $storagePath = sprintf('files/%s/%s/%s', $command->ownerId, $id->value(), $command->originalFilename);

        $this->storage->store($storagePath, $command->localTmpPath);

        $file = StoredFile::upload(
            id: $id,
            ownerId: $command->ownerId,
            originalFilename: $command->originalFilename,
            mimeType: $command->mimeType,
            sizeBytes: $command->sizeBytes,
            storagePath: $storagePath,
        );

        $this->files->save($file);

        return FileResponse::fromStoredFile($file);
    }

    private function quotaLimitBytes(): int
    {
        $setting = $this->settings->findByKey(SystemSettingKey::fromString('file_storage_quota_bytes'));

        if ($setting === null) {
            return self::DEFAULT_QUOTA_BYTES;
        }

        return (int) $setting->value();
    }
}
