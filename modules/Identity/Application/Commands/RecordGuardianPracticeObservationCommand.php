<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Commands;

final readonly class RecordGuardianPracticeObservationCommand
{
    public function __construct(
        public string $guardianUserId,
        public string $minorUserId,
        public string $enrollmentId,
        public string $lessonId,
        public string $behavior,
        public string $practiceContext,
        public string $note,
    ) {}
}
