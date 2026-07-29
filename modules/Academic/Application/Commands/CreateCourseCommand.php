<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

final readonly class CreateCourseCommand
{
    public function __construct(
        public string $code,
        public string $title,
        public ?string $description,
    ) {}
}
