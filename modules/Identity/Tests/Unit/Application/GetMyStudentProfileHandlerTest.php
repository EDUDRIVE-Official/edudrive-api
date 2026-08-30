<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Identity\Application\Queries\GetMyStudentProfileQuery;
use Modules\Identity\Application\UseCases\GetMyStudentProfileHandler;
use Modules\Identity\Domain\Entities\StudentProfile;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\StudentProfileRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

final class InMemoryUserRepositoryForMyProfile implements UserRepository
{
    /** @var array<string, User> */
    public array $items = [];

    public function save(User $user): void
    {
        $this->items[$user->id()] = $user;
    }

    public function findById(string $id): ?User
    {
        return $this->items[$id] ?? null;
    }

    public function findByEmail(Email $email): ?User
    {
        return null;
    }

    public function existsByEmail(Email $email): bool
    {
        return false;
    }

    public function delete(string $id): void
    {
        unset($this->items[$id]);
    }

    /** @return list<User> */
    public function all(): array
    {
        return array_values($this->items);
    }

    /** @return list<User> */
    public function findInactiveBefore(DateTimeImmutable $threshold): array
    {
        return [];
    }
}

final class InMemoryStudentProfileRepositoryForMyProfile implements StudentProfileRepository
{
    private ?StudentProfile $profile = null;

    public function withProfile(?StudentProfile $profile): void
    {
        $this->profile = $profile;
    }

    public function save(StudentProfile $profile): void
    {
        $this->profile = $profile;
    }

    public function findByUserId(string $userId): ?StudentProfile
    {
        return $this->profile;
    }
}

final class InMemoryRoadPassportRepositoryForMyProfile implements RoadPassportRepository
{
    private ?RoadPassport $passport = null;

    public function withPassport(?RoadPassport $passport): void
    {
        $this->passport = $passport;
    }

    public function save(RoadPassport $passport): void
    {
        $this->passport = $passport;
    }

    public function findById(RoadPassportId $id): ?RoadPassport
    {
        return $this->passport;
    }

    public function findByUserId(string $userId): ?RoadPassport
    {
        return $this->passport;
    }
}

final class InMemoryEnrollmentRepositoryForMyProfile implements EnrollmentRepository
{
    /** @var list<Enrollment> */
    private array $enrollments = [];

    /** @param list<Enrollment> $enrollments */
    public function withEnrollments(array $enrollments): void
    {
        $this->enrollments = $enrollments;
    }

    public function save(Enrollment $enrollment): void {}

    public function findById(EnrollmentId $id): ?Enrollment
    {
        return null;
    }

    public function findActiveOrPendingFor(CourseId $courseId, string $userId): ?Enrollment
    {
        return null;
    }

    public function all(
        ?CourseId $courseId = null,
        ?string $userId = null,
        ?string $organizationId = null,
        ?EnrollmentStatus $status = null,
        ?EnrollmentSource $source = null,
    ): array {
        return $this->enrollments;
    }
}

function myProfileTestUser(): User
{
    return User::register(
        id: 'user-1',
        name: 'Abel Campos',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('2010-01-01'),
    );
}

it('compone el perfil del usuario, su edad, su pasaporte vial y sus matriculas', function (): void {
    $users = new InMemoryUserRepositoryForMyProfile;
    $users->save(myProfileTestUser());

    $profiles = new InMemoryStudentProfileRepositoryForMyProfile;
    $profiles->withProfile(StudentProfile::restore(
        userId: 'user-1',
        educationLevel: 'Secundaria',
        accessibilityNeeds: null,
        learningPreferences: 'Video',
        updatedAt: new DateTimeImmutable('2026-08-30 10:00:00'),
    ));

    $roadPassports = new InMemoryRoadPassportRepositoryForMyProfile;
    $roadPassports->withPassport(RoadPassport::create(
        id: RoadPassportId::fromString('01981a64-8300-7b1d-b442-764ea7f92111'),
        userId: 'user-1',
        issuedAt: new DateTimeImmutable('2026-01-01'),
    ));

    $enrollments = new InMemoryEnrollmentRepositoryForMyProfile;
    $enrollments->withEnrollments([
        Enrollment::create(
            id: EnrollmentId::fromString('01981a64-8300-7b1d-b442-764ea7f92112'),
            courseId: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f92113'),
            userId: 'user-1',
        ),
    ]);

    $handler = new GetMyStudentProfileHandler($users, $profiles, $roadPassports, $enrollments);
    $response = $handler->handle(new GetMyStudentProfileQuery(userId: 'user-1'));

    expect($response->name)->toBe('Abel Campos')
        ->and($response->isMinor)->toBeTrue()
        ->and($response->educationLevel)->toBe('Secundaria')
        ->and($response->learningPreferences)->toBe('Video')
        ->and($response->roadPassport)->not->toBeNull()
        ->and($response->roadPassport['status'])->toBe('active')
        ->and($response->enrollments)->toHaveCount(1);
});

it('devuelve null en perfil y pasaporte vial cuando el usuario no los tiene', function (): void {
    $users = new InMemoryUserRepositoryForMyProfile;
    $users->save(myProfileTestUser());

    $handler = new GetMyStudentProfileHandler(
        $users,
        new InMemoryStudentProfileRepositoryForMyProfile,
        new InMemoryRoadPassportRepositoryForMyProfile,
        new InMemoryEnrollmentRepositoryForMyProfile,
    );

    $response = $handler->handle(new GetMyStudentProfileQuery(userId: 'user-1'));

    expect($response->educationLevel)->toBeNull()
        ->and($response->roadPassport)->toBeNull()
        ->and($response->enrollments)->toBeEmpty();
});

it('rechaza consultar el perfil de un usuario inexistente', function (): void {
    $handler = new GetMyStudentProfileHandler(
        new InMemoryUserRepositoryForMyProfile,
        new InMemoryStudentProfileRepositoryForMyProfile,
        new InMemoryRoadPassportRepositoryForMyProfile,
        new InMemoryEnrollmentRepositoryForMyProfile,
    );

    $handler->handle(new GetMyStudentProfileQuery(userId: 'no-existe'));
})->throws(UserNotFound::class);
