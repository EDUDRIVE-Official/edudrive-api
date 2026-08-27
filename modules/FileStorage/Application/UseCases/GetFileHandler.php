<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\UseCases;

use Modules\FileStorage\Application\Exceptions\FileNotFound;
use Modules\FileStorage\Application\Queries\GetFileQuery;
use Modules\FileStorage\Application\Responses\FileResponse;
use Modules\FileStorage\Domain\Repositories\FileRepository;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;

final readonly class GetFileHandler
{
    public function __construct(private FileRepository $files) {}

    public function handle(GetFileQuery $query): FileResponse
    {
        $file = $this->files->findById(StoredFileId::fromString($query->fileId));

        if ($file === null) {
            throw FileNotFound::withId($query->fileId);
        }

        if (! $file->isOwnedBy($query->requestingUserId) && ! $query->canViewOthers) {
            throw FileNotFound::withId($query->fileId);
        }

        return FileResponse::fromStoredFile($file);
    }
}
