<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class UploadFileCommand implements Command
{
    public function __construct(
        public string $ownerId,
        public string $originalFilename,
        public string $mimeType,
        public int $sizeBytes,
        public string $localTmpPath,
    ) {}
}
