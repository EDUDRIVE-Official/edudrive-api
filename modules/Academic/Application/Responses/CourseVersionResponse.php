<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Entities\CourseVersion;

final readonly class CourseVersionResponse
{
    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function __construct(
        public string $id,
        public int $versionNumber,
        public string $status,
        public string $publishedAt,
        public ?string $archivedAt,
        public array $snapshot,
    ) {}

    public static function fromVersion(CourseVersion $version): self
    {
        return new self(
            id: $version->id(),
            versionNumber: $version->versionNumber(),
            status: $version->status()->value,
            publishedAt: $version->publishedAt()->format(DATE_ATOM),
            archivedAt: $version->archivedAt()?->format(DATE_ATOM),
            snapshot: $version->snapshot(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'version_number' => $this->versionNumber,
            'status' => $this->status,
            'published_at' => $this->publishedAt,
            'archived_at' => $this->archivedAt,
            'snapshot' => $this->snapshot,
        ];
    }
}
