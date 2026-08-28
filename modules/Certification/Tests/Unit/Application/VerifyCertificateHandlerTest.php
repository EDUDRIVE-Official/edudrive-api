<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Certification\Application\Exceptions\CertificateNotFound;
use Modules\Certification\Application\Queries\VerifyCertificateQuery;
use Modules\Certification\Application\Responses\CertificateVerificationResponse;
use Modules\Certification\Application\UseCases\VerifyCertificateHandler;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Enums\CertificateStatus;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final class InMemoryVerificationCertificateRepository implements CertificateRepository
{
    /** @var array<string, Certificate> */
    public array $items = [];

    public function save(Certificate $certificate): void
    {
        $this->items[$certificate->id()->value()] = $certificate;
    }

    public function findById(CertificateId $id): ?Certificate
    {
        return $this->items[$id->value()] ?? null;
    }

    public function findByUserAndCourse(string $userId, string $courseId): ?Certificate
    {
        throw new LogicException('No usado en esta prueba.');
    }

    public function findByValidationCode(ValidationCode $validationCode): ?Certificate
    {
        foreach ($this->items as $certificate) {
            if ($certificate->validationCode()->equals($validationCode)) {
                return $certificate;
            }
        }

        return null;
    }

    /** @return list<Certificate> */
    public function allForUser(string $userId): array
    {
        throw new LogicException('No usado en esta prueba.');
    }
}

final class InMemoryVerificationUserRepository implements UserRepository
{
    /** @var array<string, User> */
    public array $users = [];

    public function save(User $user): void
    {
        $this->users[$user->id()] = $user;
    }

    public function findById(string $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findByEmail(Email $email): ?User
    {
        throw new LogicException('No usado en esta prueba.');
    }

    public function existsByEmail(Email $email): bool
    {
        throw new LogicException('No usado en esta prueba.');
    }

    public function delete(string $id): void
    {
        throw new LogicException('No usado en esta prueba.');
    }

    /** @return list<User> */
    public function all(): array
    {
        throw new LogicException('No usado en esta prueba.');
    }

    /** @return list<User> */
    public function findInactiveBefore(DateTimeImmutable $threshold): array
    {
        throw new LogicException('No usado en esta prueba.');
    }
}

final class InMemoryVerificationCourseRepository implements CourseRepository
{
    /** @var array<string, Course> */
    public array $courses = [];

    public function save(Course $course): void
    {
        $this->courses[$course->id()->value()] = $course;
    }

    public function updateAtomically(CourseId $id, Closure $mutation): ?Course
    {
        throw new LogicException('No usado en esta prueba.');
    }

    public function updateAtomicallyWithContentCoverage(CourseId $id, Closure $mutation): ?Course
    {
        throw new LogicException('No usado en esta prueba.');
    }

    public function findById(CourseId $id): ?Course
    {
        return $this->courses[$id->value()] ?? null;
    }

    public function findByCode(CourseCode $code): ?Course
    {
        throw new LogicException('No usado en esta prueba.');
    }

    public function existsByCode(CourseCode $code): bool
    {
        throw new LogicException('No usado en esta prueba.');
    }

    /** @return list<Course> */
    public function all(): array
    {
        throw new LogicException('No usado en esta prueba.');
    }
}

function persistedVerificationUser(InMemoryVerificationUserRepository $repository, string $name): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: $name,
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $repository->save($user);

    return $user;
}

function persistedVerificationCourse(InMemoryVerificationCourseRepository $repository, string $title): Course
{
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('CRT-'.strtoupper((string) Str::random(6))),
        title: CourseTitle::fromString($title),
    );
    $repository->save($course);

    return $course;
}

it('verifica un certificado valido por su codigo y expone el nombre del titular y del curso', function (): void {
    $certificates = new InMemoryVerificationCertificateRepository;
    $users = new InMemoryVerificationUserRepository;
    $courses = new InMemoryVerificationCourseRepository;

    $user = persistedVerificationUser($users, 'Ana Torres');
    $course = persistedVerificationCourse($courses, 'Manejo defensivo');
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $user->id(),
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
    );
    $certificates->save($certificate);

    $response = (new VerifyCertificateHandler($certificates, $users, $courses))
        ->handle(new VerifyCertificateQuery($certificate->validationCode()->value()));

    expect($response)->toBeInstanceOf(CertificateVerificationResponse::class)
        ->and($response->validationCode)->toBe($certificate->validationCode()->value())
        ->and($response->status)->toBe('valid')
        ->and($response->courseId)->toBe($course->id()->value())
        ->and($response->courseName)->toBe('Manejo defensivo')
        ->and($response->holderName)->toBe('Ana Torres')
        ->and($response->expiresAt)->toBeNull();
});

it('verifica un certificado cuyo titular fue eliminado sin exponer nombre alguno', function (): void {
    $certificates = new InMemoryVerificationCertificateRepository;
    $users = new InMemoryVerificationUserRepository;
    $courses = new InMemoryVerificationCourseRepository;

    $course = persistedVerificationCourse($courses, 'Manejo defensivo');
    $certificate = Certificate::restore(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: null,
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
        status: CertificateStatus::Issued,
        issuedAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
        expiresAt: null,
        history: [],
    );
    $certificates->save($certificate);

    $response = (new VerifyCertificateHandler($certificates, $users, $courses))
        ->handle(new VerifyCertificateQuery($certificate->validationCode()->value()));

    expect($response->status)->toBe('valid')
        ->and($response->holderName)->toBeNull();
});

it('normaliza el codigo de validacion recibido antes de buscarlo', function (): void {
    $certificates = new InMemoryVerificationCertificateRepository;
    $users = new InMemoryVerificationUserRepository;
    $courses = new InMemoryVerificationCourseRepository;

    $user = persistedVerificationUser($users, 'Ana Torres');
    $course = persistedVerificationCourse($courses, 'Manejo defensivo');
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $user->id(),
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
    );
    $certificates->save($certificate);

    $lowercaseCode = strtolower($certificate->validationCode()->value());
    $response = (new VerifyCertificateHandler($certificates, $users, $courses))
        ->handle(new VerifyCertificateQuery($lowercaseCode));

    expect($response->validationCode)->toBe($certificate->validationCode()->value());
});

it('reporta expired cuando la vigencia ya paso', function (): void {
    $certificates = new InMemoryVerificationCertificateRepository;
    $users = new InMemoryVerificationUserRepository;
    $courses = new InMemoryVerificationCourseRepository;

    $user = persistedVerificationUser($users, 'Ana Torres');
    $course = persistedVerificationCourse($courses, 'Manejo defensivo');
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $user->id(),
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
        expiresAt: new DateTimeImmutable('2020-01-01T00:00:00+00:00'),
    );
    $certificates->save($certificate);

    $response = (new VerifyCertificateHandler($certificates, $users, $courses))
        ->handle(new VerifyCertificateQuery($certificate->validationCode()->value()));

    expect($response->status)->toBe('expired');
});

it('reporta revoked sin importar la vigencia', function (): void {
    $certificates = new InMemoryVerificationCertificateRepository;
    $users = new InMemoryVerificationUserRepository;
    $courses = new InMemoryVerificationCourseRepository;

    $user = persistedVerificationUser($users, 'Ana Torres');
    $course = persistedVerificationCourse($courses, 'Manejo defensivo');
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $user->id(),
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
    );
    $certificate->revoke('Fraude', new DateTimeImmutable('now'));
    $certificates->save($certificate);

    $response = (new VerifyCertificateHandler($certificates, $users, $courses))
        ->handle(new VerifyCertificateQuery($certificate->validationCode()->value()));

    expect($response->status)->toBe('revoked');
});

it('rechaza un codigo con formato invalido sin distinguirlo de uno inexistente', function (): void {
    $certificates = new InMemoryVerificationCertificateRepository;
    $users = new InMemoryVerificationUserRepository;
    $courses = new InMemoryVerificationCourseRepository;

    expect(fn () => (new VerifyCertificateHandler($certificates, $users, $courses))
        ->handle(new VerifyCertificateQuery('formato-invalido')))
        ->toThrow(CertificateNotFound::class);
});

it('rechaza un codigo con formato valido pero inexistente', function (): void {
    $certificates = new InMemoryVerificationCertificateRepository;
    $users = new InMemoryVerificationUserRepository;
    $courses = new InMemoryVerificationCourseRepository;

    expect(fn () => (new VerifyCertificateHandler($certificates, $users, $courses))
        ->handle(new VerifyCertificateQuery(ValidationCode::generate()->value())))
        ->toThrow(CertificateNotFound::class);
});
