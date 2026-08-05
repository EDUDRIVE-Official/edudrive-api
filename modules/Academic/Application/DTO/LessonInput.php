<?php

declare(strict_types=1);

namespace Modules\Academic\Application\DTO;

final readonly class LessonInput
{
    /** @param list<ContentBlockInput> $blocks */
    public function __construct(
        public string $id,
        public string $code,
        public string $title,
        public ?string $summary,
        public ?int $durationMinutes,
        public int $position,
        public array $blocks,
    ) {}
}
