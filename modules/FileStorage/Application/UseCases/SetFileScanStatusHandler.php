<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\UseCases;

use Modules\FileStorage\Application\Commands\SetFileScanStatusCommand;
use Modules\FileStorage\Application\Exceptions\FileNotFound;
use Modules\FileStorage\Application\Responses\FileResponse;
use Modules\FileStorage\Domain\Enums\FileScanStatus;
use Modules\FileStorage\Domain\Repositories\FileRepository;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;

final readonly class SetFileScanStatusHandler
{
    public function __construct(private FileRepository $files) {}

    public function handle(SetFileScanStatusCommand $command): FileResponse
    {
        $file = $this->files->findById(StoredFileId::fromString($command->fileId));

        if ($file === null) {
            throw FileNotFound::withId($command->fileId);
        }

        $file->setScanStatus(FileScanStatus::from($command->scanStatus));
        $this->files->save($file);

        return FileResponse::fromStoredFile($file);
    }
}
