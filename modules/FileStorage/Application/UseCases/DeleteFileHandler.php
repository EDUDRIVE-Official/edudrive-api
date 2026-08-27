<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\UseCases;

use Modules\FileStorage\Application\Commands\DeleteFileCommand;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Modules\FileStorage\Application\Exceptions\FileNotFound;
use Modules\FileStorage\Domain\Repositories\FileRepository;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;

final readonly class DeleteFileHandler
{
    public function __construct(
        private FileRepository $files,
        private FileStorage $storage,
    ) {}

    public function handle(DeleteFileCommand $command): void
    {
        $id = StoredFileId::fromString($command->fileId);
        $file = $this->files->findById($id);

        if ($file === null) {
            throw FileNotFound::withId($command->fileId);
        }

        if (! $file->isOwnedBy($command->requestingUserId) && ! $command->canManageOthers) {
            throw FileNotFound::withId($command->fileId);
        }

        $this->storage->delete($file->storagePath());
        $this->files->delete($id);
    }
}
