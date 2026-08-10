<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Entities\CourseVersion;

final readonly class CourseVersionListItemResponse
{
    private function __construct(
        public int $versionNumber,
        public string $status,
        public string $publishedAt,
        public ?string $archivedAt,
    ) {}

    public static function fromVersion(CourseVersion $version): self
    {
        return new self(
            versionNumber: $version->versionNumber(),
            status: $version->status()->value,
            publishedAt: $version->publishedAt()->format(DATE_ATOM),
            archivedAt: $version->archivedAt()?->format(DATE_ATOM),
        );
    }

    /**
     * @return array{version_number: int, status: string, published_at: string, archived_at: string|null}
     */
    public function toArray(): array
    {
        return [
            'version_number' => $this->versionNumber,
            'status' => $this->status,
            'published_at' => $this->publishedAt,
            'archived_at' => $this->archivedAt,
        ];
    }
}
