<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\CreateBulkInstitutionalEnrollmentsCommand;
use Modules\Academic\Application\Commands\CreateInstitutionalEnrollmentCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Responses\BulkEnrollmentResponse;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class CreateBulkInstitutionalEnrollmentsHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private CourseRepository $courses,
    ) {}

    public function handle(CreateBulkInstitutionalEnrollmentsCommand $command): BulkEnrollmentResponse
    {
        $courseId = CourseId::fromString($command->courseId);
        if ($this->courses->findById($courseId) === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        $created = 0;
        $failed = 0;
        $results = [];

        foreach ($command->userIds as $userId) {
            if ($this->enrollments->findActiveOrPendingFor($courseId, $userId) !== null) {
                $failed++;
                $results[] = [
                    'user_id' => $userId,
                    'created' => false,
                    'error_code' => 'ENROLLMENT_ALREADY_EXISTS',
                ];

                continue;
            }

            $response = (new CreateInstitutionalEnrollmentHandler($this->enrollments, $this->courses))->handle(
                new CreateInstitutionalEnrollmentCommand(
                    courseId: $command->courseId,
                    userId: $userId,
                    organizationId: $command->organizationId,
                    status: $command->status,
                    startsAt: $command->startsAt,
                    endsAt: $command->endsAt,
                ),
            );

            $created++;
            $results[] = [
                'user_id' => $userId,
                'created' => true,
                'enrollment_id' => $response->id,
            ];
        }

        return new BulkEnrollmentResponse(
            total: count($command->userIds),
            created: $created,
            failed: $failed,
            results: $results,
        );
    }
}
