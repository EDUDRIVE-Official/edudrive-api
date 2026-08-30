<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Identity\Application\Exceptions\GuardianRelationshipNotFound;
use Modules\Identity\Application\Queries\GetLinkedMinorProgressQuery;
use Modules\Identity\Application\Services\StudentProfileComposer;
use Modules\Identity\Application\UseCases\GetLinkedMinorProgressHandler;
use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Domain\Entities\StudentProfile;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Domain\Repositories\StudentProfileRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

final class InMemoryGuardianRelationshipRepositoryForProgress implements GuardianRelationshipRepository
{
    /** @var array<string, GuardianRelationship> */
    public array $items = [];

    public function save(GuardianRelationship $relationship): void
    {
        $this->items[$relationship->id()] = $relationship;
    }

    public function findById(string $id): ?GuardianRelationship
    {
        return $this->items[$id] ?? null;
    }

    public function findActiveByGuardianAndMinor(string $guardianUserId, string $minorUserId): ?GuardianRelationship
    {
        foreach ($this->items as $relationship) {
            if ($relationship->guardianUserId() === $guardianUserId
                && $relationship->minorUserId() === $minorUserId
                && $relationship->isActive()) {
                return $relationship;
            }
        }

        return null;
    }

    /** @return list<GuardianRelationship> */
    public function findActiveByGuardian(string $guardianUserId): array
    {
        return [];
    }
}

final class InMemoryUserRepositoryForProgress implements UserRepository
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

final class InMemoryStudentProfileRepositoryForProgress implements StudentProfileRepository
{
    public function save(StudentProfile $profile): void {}

    public function findByUserId(string $userId): ?StudentProfile
    {
        return null;
    }
}

final class InMemoryRoadPassportRepositoryForProgress implements RoadPassportRepository
{
    public function save(RoadPassport $passport): void {}

    public function findById(RoadPassportId $id): ?RoadPassport
    {
        return null;
    }

    public function findByUserId(string $userId): ?RoadPassport
    {
        return null;
    }
}

final class InMemoryEnrollmentRepositoryForProgress implements EnrollmentRepository
{
    public function save(Enrollment $enrollment): void {}

    public function findById(EnrollmentId $id): ?Enrollment
    {
        return null;
    }

    public function findActiveOrPendingFor(CourseId $courseId, string $userId): ?Enrollment
    {
        return null;
    }

    /** @return list<Enrollment> */
    public function all(
        ?CourseId $courseId = null,
        ?string $userId = null,
        ?string $organizationId = null,
        ?EnrollmentStatus $status = null,
        ?EnrollmentSource $source = null,
    ): array {
        return [];
    }
}

function progressTestMinor(string $id): User
{
    return User::register(
        id: $id,
        name: 'Menor vinculado',
        email: Email::fromString($id.'@edudrive.cr'),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('2015-01-01'),
    );
}

it('devuelve el progreso del menor cuando existe una relacion activa', function (): void {
    $relationships = new InMemoryGuardianRelationshipRepositoryForProgress;
    $relationships->save(GuardianRelationship::create(
        id: 'relationship-1',
        guardianUserId: 'guardian-1',
        minorUserId: 'minor-1',
    ));

    $users = new InMemoryUserRepositoryForProgress;
    $users->save(progressTestMinor('minor-1'));

    $composer = new StudentProfileComposer(
        $users,
        new InMemoryStudentProfileRepositoryForProgress,
        new InMemoryRoadPassportRepositoryForProgress,
        new InMemoryEnrollmentRepositoryForProgress,
    );

    $handler = new GetLinkedMinorProgressHandler($relationships, $composer);
    $response = $handler->handle(new GetLinkedMinorProgressQuery(guardianUserId: 'guardian-1', minorUserId: 'minor-1'));

    expect($response->userId)->toBe('minor-1')
        ->and($response->isMinor)->toBeTrue();
});

it('rechaza consultar el progreso de un menor sin relacion activa', function (): void {
    $users = new InMemoryUserRepositoryForProgress;
    $users->save(progressTestMinor('minor-1'));

    $composer = new StudentProfileComposer(
        $users,
        new InMemoryStudentProfileRepositoryForProgress,
        new InMemoryRoadPassportRepositoryForProgress,
        new InMemoryEnrollmentRepositoryForProgress,
    );

    $handler = new GetLinkedMinorProgressHandler(new InMemoryGuardianRelationshipRepositoryForProgress, $composer);

    $handler->handle(new GetLinkedMinorProgressQuery(guardianUserId: 'guardian-1', minorUserId: 'minor-1'));
})->throws(GuardianRelationshipNotFound::class);

it('rechaza consultar el progreso de un menor cuya relacion ya fue revocada', function (): void {
    $relationships = new InMemoryGuardianRelationshipRepositoryForProgress;
    $relationship = GuardianRelationship::create(
        id: 'relationship-1',
        guardianUserId: 'guardian-1',
        minorUserId: 'minor-1',
    );
    $relationship->revoke(new DateTimeImmutable);
    $relationships->save($relationship);

    $users = new InMemoryUserRepositoryForProgress;
    $users->save(progressTestMinor('minor-1'));

    $composer = new StudentProfileComposer(
        $users,
        new InMemoryStudentProfileRepositoryForProgress,
        new InMemoryRoadPassportRepositoryForProgress,
        new InMemoryEnrollmentRepositoryForProgress,
    );

    $handler = new GetLinkedMinorProgressHandler($relationships, $composer);

    $handler->handle(new GetLinkedMinorProgressQuery(guardianUserId: 'guardian-1', minorUserId: 'minor-1'));
})->throws(GuardianRelationshipNotFound::class);
