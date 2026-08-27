<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\UseCases;

use Modules\FileStorage\Application\Queries\GetMyFilesQuery;
use Modules\FileStorage\Application\Responses\FileResponse;
use Modules\FileStorage\Domain\Aggregates\StoredFile;
use Modules\FileStorage\Domain\Repositories\FileRepository;

final readonly class GetMyFilesHandler
{
    public function __construct(private FileRepository $files) {}

    /** @return list<FileResponse> */
    public function handle(GetMyFilesQuery $query): array
    {
        return array_map(
            static fn (StoredFile $file): FileResponse => FileResponse::fromStoredFile($file),
            $this->files->allForOwner($query->ownerId),
        );
    }
}
