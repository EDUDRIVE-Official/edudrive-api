<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\CreateBulkEnrollmentsCommand;
use Modules\Academic\Application\Commands\CreateEnrollmentCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Responses\BulkEnrollmentResponse;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class CreateBulkEnrollmentsHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private CourseRepository $courses,
    ) {}

    public function handle(CreateBulkEnrollmentsCommand $command): BulkEnrollmentResponse
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

            $response = (new CreateEnrollmentHandler($this->enrollments, $this->courses))->handle(
                new CreateEnrollmentCommand(
                    courseId: $command->courseId,
                    userId: $userId,
                    status: $command->status,
                    source: $command->source,
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
