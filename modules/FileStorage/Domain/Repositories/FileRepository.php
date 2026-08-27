<?php

declare(strict_types=1);

namespace Modules\FileStorage\Domain\Repositories;

use Modules\FileStorage\Domain\Aggregates\StoredFile;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;

interface FileRepository
{
    public function save(StoredFile $file): void;

    public function findById(StoredFileId $id): ?StoredFile;

    /** @return list<StoredFile> */
    public function allForOwner(string $ownerId): array;

    public function totalBytesForOwner(string $ownerId): int;

    public function delete(StoredFileId $id): void;
}
