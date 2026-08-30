<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Commands;

final readonly class UpdateStudentProfileCommand
{
    public function __construct(
        public string $userId,
        public ?string $educationLevel,
        public ?string $accessibilityNeeds,
        public ?string $learningPreferences,
    ) {}
}
