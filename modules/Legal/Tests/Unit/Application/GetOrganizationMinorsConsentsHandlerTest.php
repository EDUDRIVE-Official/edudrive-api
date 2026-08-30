<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Legal\Application\Queries\GetOrganizationMinorsConsentsQuery;
use Modules\Legal\Application\UseCases\GetOrganizationMinorsConsentsHandler;
use Modules\Legal\Domain\Entities\UserConsent;
use Modules\Legal\Domain\Repositories\UserConsentRepository;
use Modules\Legal\Domain\ValueObjects\PolicyKey;
use Modules\Organization\Domain\ValueObjects\OrganizationId;

final class InMemoryOrgConsentsEnrollmentRepository implements EnrollmentRepository
{
    /** @var list<Enrollment> */
    public array $items = [];

    public function save(Enrollment $enrollment): void
    {
        $this->items[] = $enrollment;
    }

    public function findById(EnrollmentId $id): ?Enrollment
    {
        throw new LogicException('No usado en esta prueba.');
    }

    public function findActiveOrPendingFor(CourseId $courseId, string $userId): ?Enrollment
    {
        throw new LogicException('No usado en esta prueba.');
    }

    /** @return list<Enrollment> */
    public function all(
        ?CourseId $courseId = null,
        ?string $userId = null,
        ?string $organizationId = null,
        ?EnrollmentStatus $status = null,
        ?EnrollmentSource $source = null,
    ): array {
        return array_values(array_filter(
            $this->items,
            static fn (Enrollment $enrollment): bool => $organizationId === null
                || $enrollment->organizationId()?->value() === $organizationId,
        ));
    }
}

final class InMemoryOrgConsentsUserRepository implements UserRepository
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

final class InMemoryOrgConsentsUserConsentRepository implements UserConsentRepository
{
    /** @var list<UserConsent> */
    public array $items = [];

    public function save(UserConsent $consent): void
    {
        $this->items[] = $consent;
    }

    /** @return list<UserConsent> */
    public function findByUserId(string $userId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (UserConsent $consent): bool => $consent->userId() === $userId,
        ));
    }

    public function findLatestActiveByUserAndPolicy(string $userId, PolicyKey $policyKey): ?UserConsent
    {
        return null;
    }
}

it('lista unicamente los estudiantes menores de una organizacion con su historial de consentimiento', function (): void {
    $enrollments = new InMemoryOrgConsentsEnrollmentRepository;
    $users = new InMemoryOrgConsentsUserRepository;
    $consents = new InMemoryOrgConsentsUserConsentRepository;

    $organizationId = (string) Str::uuid();

    $minor = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante Menor',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('-15 years'),
    );
    $users->save($minor);
    $consents->save(UserConsent::accept(
        id: (string) Str::uuid(),
        userId: $minor->id(),
        policyKey: PolicyKey::fromString('privacy_policy'),
        policyVersion: 1,
        guardianDeclaration: 'María Pérez',
    ));

    $adult = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante Adulto',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('-30 years'),
    );
    $users->save($adult);

    $enrollments->save(Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: $minor->id(),
        organizationId: OrganizationId::fromString($organizationId),
        source: EnrollmentSource::Institutional,
    ));
    $enrollments->save(Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: $adult->id(),
        organizationId: OrganizationId::fromString($organizationId),
        source: EnrollmentSource::Institutional,
    ));
    $enrollments->save(Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        organizationId: OrganizationId::fromString((string) Str::uuid()),
        source: EnrollmentSource::Institutional,
    ));

    $responses = (new GetOrganizationMinorsConsentsHandler($enrollments, $users, $consents))
        ->handle(new GetOrganizationMinorsConsentsQuery(organizationId: $organizationId));

    expect($responses)->toHaveCount(1)
        ->and($responses[0]->userId)->toBe($minor->id())
        ->and($responses[0]->consents)->toHaveCount(1)
        ->and($responses[0]->consents[0]->guardianDeclaration)->toBe('María Pérez');
});
