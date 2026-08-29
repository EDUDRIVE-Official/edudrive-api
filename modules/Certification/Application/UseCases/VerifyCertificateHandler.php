<?php

declare(strict_types=1);

namespace Modules\Certification\Application\UseCases;

use InvalidArgumentException;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Certification\Application\Exceptions\CertificateNotFound;
use Modules\Certification\Application\Queries\VerifyCertificateQuery;
use Modules\Certification\Application\Responses\CertificateVerificationResponse;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\ValidationCode;
use Modules\Identity\Domain\Repositories\UserRepository;

final readonly class VerifyCertificateHandler
{
    public function __construct(
        private CertificateRepository $certificates,
        private UserRepository $users,
        private CourseRepository $courses,
    ) {}

    public function handle(VerifyCertificateQuery $query): CertificateVerificationResponse
    {
        try {
            $validationCode = ValidationCode::fromString($query->validationCode);
        } catch (InvalidArgumentException) {
            throw CertificateNotFound::withValidationCode($query->validationCode);
        }

        $certificate = $this->certificates->findByValidationCode($validationCode);
        if ($certificate === null) {
            throw CertificateNotFound::withValidationCode($query->validationCode);
        }

        $holder = $certificate->userId() === null ? null : $this->users->findById($certificate->userId());

        $course = $this->courses->findById(CourseId::fromString($certificate->courseId()));
        assert($course instanceof Course);

        return CertificateVerificationResponse::fromCertificate(
            certificate: $certificate,
            courseName: $course->title()->value(),
            holderName: $holder !== null && ! $holder->isMinor() ? $holder->name() : null,
        );
    }
}
