<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\ActivateEnrollmentCommand;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Responses\EnrollmentResponse;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

final readonly class ActivateEnrollmentHandler
{
    public function __construct(private EnrollmentRepository $enrollments) {}

    public function handle(ActivateEnrollmentCommand $command): EnrollmentResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($command->enrollmentId));
        if ($enrollment === null) {
            throw EnrollmentNotFound::withId($command->enrollmentId);
        }

        $enrollment->activate();
        $this->enrollments->save($enrollment);

        return EnrollmentResponse::fromEnrollment($enrollment);
    }
}
