<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Responses;

use DateTimeInterface;

final readonly class SystemHealthResponse
{
    public function __construct(
        public string $status,
        public string $database,
        public string $checkedAt,
    ) {}

    public static function fromDatabaseUp(bool $databaseUp, DateTimeInterface $checkedAt): self
    {
        return new self(
            status: $databaseUp ? 'healthy' : 'unhealthy',
            database: $databaseUp ? 'up' : 'down',
            checkedAt: $checkedAt->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'database' => $this->database,
            'checked_at' => $this->checkedAt,
        ];
    }
}
