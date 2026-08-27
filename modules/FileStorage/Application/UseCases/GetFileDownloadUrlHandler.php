<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\UseCases;

use DateTimeImmutable;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Modules\FileStorage\Application\Exceptions\FileNotFound;
use Modules\FileStorage\Application\Queries\GetFileDownloadUrlQuery;
use Modules\FileStorage\Application\Responses\FileDownloadUrlResponse;
use Modules\FileStorage\Domain\Repositories\FileRepository;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;

final readonly class GetFileDownloadUrlHandler
{
    private const int URL_LIFETIME_MINUTES = 15;

    public function __construct(
        private FileRepository $files,
        private FileStorage $storage,
    ) {}

    public function handle(GetFileDownloadUrlQuery $query): FileDownloadUrlResponse
    {
        $file = $this->files->findById(StoredFileId::fromString($query->fileId));

        if ($file === null) {
            throw FileNotFound::withId($query->fileId);
        }

        if (! $file->isOwnedBy($query->requestingUserId) && ! $query->canViewOthers) {
            throw FileNotFound::withId($query->fileId);
        }

        $expiresAt = new DateTimeImmutable('+'.self::URL_LIFETIME_MINUTES.' minutes');

        return new FileDownloadUrlResponse(
            url: $this->storage->temporaryDownloadUrl($file->storagePath(), $expiresAt),
            expiresAt: $expiresAt->format(DATE_ATOM),
        );
    }
}
