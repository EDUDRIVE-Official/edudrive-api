<?php

declare(strict_types=1);

namespace Modules\Foundation\Application\Responses;

final readonly class ExportResponse
{
    public function __construct(
        public string $url,
        public string $expiresAt,
        public int $rowCount,
        public string $format,
    ) {}

    /** @return array{url: string, expires_at: string, row_count: int, format: string} */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'expires_at' => $this->expiresAt,
            'row_count' => $this->rowCount,
            'format' => $this->format,
        ];
    }
}
