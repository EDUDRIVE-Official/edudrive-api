<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\Responses;

final readonly class FileDownloadUrlResponse
{
    public function __construct(
        public string $url,
        public string $expiresAt,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'expires_at' => $this->expiresAt,
        ];
    }
}
