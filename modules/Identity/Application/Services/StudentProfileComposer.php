<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Services;

use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Identity\Application\Responses\MyStudentProfileResponse;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\StudentProfileRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;

final readonly class StudentProfileComposer
{
    public function __construct(
        private UserRepository $users,
        private StudentProfileRepository $profiles,
        private RoadPassportRepository $roadPassports,
        private EnrollmentRepository $enrollments,
    ) {}

    public function compose(string $userId): MyStudentProfileResponse
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new UserNotFound;
        }

        $profile = $this->profiles->findByUserId($userId);
        $roadPassport = $this->roadPassports->findByUserId($userId);

        return new MyStudentProfileResponse(
            userId: $user->id(),
            name: $user->name(),
            dateOfBirth: $user->dateOfBirth()?->format('Y-m-d'),
            isMinor: $user->isMinor(),
            educationLevel: $profile?->educationLevel(),
            accessibilityNeeds: $profile?->accessibilityNeeds(),
            learningPreferences: $profile?->learningPreferences(),
            roadPassport: $roadPassport === null ? null : [
                'status' => $roadPassport->status()->value,
                'level' => $roadPassport->level(),
                'issued_at' => $roadPassport->issuedAt()->format(DATE_ATOM),
            ],
            enrollments: array_map(
                static fn (Enrollment $enrollment): array => [
                    'course_id' => $enrollment->courseId()->value(),
                    'status' => $enrollment->status()->value,
                    'enrolled_at' => $enrollment->enrolledAt()->format(DATE_ATOM),
                ],
                $this->enrollments->all(userId: $userId),
            ),
        );
    }
}
