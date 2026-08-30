<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Commands;

final readonly class UpdateTeacherProfileCommand
{
    public function __construct(
        public string $userId,
        public ?string $specialties,
        public ?string $certifications,
    ) {}
}
