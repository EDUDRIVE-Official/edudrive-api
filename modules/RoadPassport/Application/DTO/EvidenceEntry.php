<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\DTO;

use Modules\RoadPassport\Domain\Enums\EvidenceType;

final readonly class EvidenceEntry
{
    /** @param array<string, mixed> $details */
    public function __construct(
        public string $userId,
        public EvidenceType $type,
        public string $subjectId,
        public string $courseId,
        public array $details,
    ) {}
}
