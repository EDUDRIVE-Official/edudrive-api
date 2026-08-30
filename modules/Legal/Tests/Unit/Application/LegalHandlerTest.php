<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Legal\Application\Commands\PublishPolicyVersionCommand;
use Modules\Legal\Application\Commands\RecordConsentCommand;
use Modules\Legal\Application\Commands\RevokeConsentCommand;
use Modules\Legal\Application\Exceptions\ConsentNotFound;
use Modules\Legal\Application\Queries\GetMyConsentsQuery;
use Modules\Legal\Application\Queries\ListPoliciesQuery;
use Modules\Legal\Application\Responses\ConsentResponse;
use Modules\Legal\Application\Responses\PolicyResponse;
use Modules\Legal\Application\UseCases\GetMyConsentsHandler;
use Modules\Legal\Application\UseCases\ListPoliciesHandler;
use Modules\Legal\Application\UseCases\PublishPolicyVersionHandler;
use Modules\Legal\Application\UseCases\RecordConsentHandler;
use Modules\Legal\Application\UseCases\RevokeConsentHandler;
use Modules\Legal\Domain\Aggregates\ConsentPolicy;
use Modules\Legal\Domain\Entities\UserConsent;
use Modules\Legal\Domain\Exceptions\GuardianDeclarationRequired;
use Modules\Legal\Domain\Exceptions\PolicyNotFound;
use Modules\Legal\Domain\Repositories\ConsentPolicyRepository;
use Modules\Legal\Domain\Repositories\UserConsentRepository;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

final class InMemoryConsentPolicyRepository implements ConsentPolicyRepository
{
    /** @var array<string, list<ConsentPolicy>> */
    public array $byKey = [];

    public function save(ConsentPolicy $policy): void
    {
        $this->byKey[$policy->key()->value()][] = $policy;
    }

    public function findCurrentByKey(PolicyKey $key): ?ConsentPolicy
    {
        $versions = $this->byKey[$key->value()] ?? [];

        if ($versions === []) {
            return null;
        }

        usort($versions, static fn (ConsentPolicy $a, ConsentPolicy $b): int => $b->version() <=> $a->version());

        return $versions[0];
    }

    /** @return list<ConsentPolicy> */
    public function allCurrent(): array
    {
        return array_values(array_map(
            fn (string $key): ConsentPolicy => $this->findCurrentByKey(PolicyKey::fromString($key)),
            array_keys($this->byKey),
        ));
    }
}

final class InMemoryUserConsentRepository implements UserConsentRepository
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
        $candidates = array_values(array_filter(
            $this->items,
            static fn (UserConsent $consent): bool => $consent->userId() === $userId
                && $consent->policyKey()->equals($policyKey)
                && ! $consent->isRevoked(),
        ));

        usort($candidates, static fn (UserConsent $a, UserConsent $b): int => $b->acceptedAt() <=> $a->acceptedAt());

        return $candidates[0] ?? null;
    }
}

final class InMemoryLegalUserRepository implements UserRepository
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

function legalAdultUser(): User
{
    return User::register(
        id: (string) Str::uuid(),
        name: 'Usuario adulto',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('-30 years'),
    );
}

function legalMinorUser(): User
{
    return User::register(
        id: (string) Str::uuid(),
        name: 'Usuario menor',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('-15 years'),
    );
}

it('publica la primera version de una politica nueva', function (): void {
    $policies = new InMemoryConsentPolicyRepository;

    $response = (new PublishPolicyVersionHandler($policies))
        ->handle(new PublishPolicyVersionCommand(key: 'privacy_policy'));

    expect($response)->toBeInstanceOf(PolicyResponse::class)
        ->and($response->version)->toBe(1);
});

it('incrementa la version al publicar de nuevo la misma politica', function (): void {
    $policies = new InMemoryConsentPolicyRepository;
    (new PublishPolicyVersionHandler($policies))->handle(new PublishPolicyVersionCommand(key: 'privacy_policy'));

    $response = (new PublishPolicyVersionHandler($policies))
        ->handle(new PublishPolicyVersionCommand(key: 'privacy_policy'));

    expect($response->version)->toBe(2);
});

it('registra el consentimiento de un usuario adulto a la version vigente', function (): void {
    $policies = new InMemoryConsentPolicyRepository;
    $consents = new InMemoryUserConsentRepository;
    $users = new InMemoryLegalUserRepository;
    (new PublishPolicyVersionHandler($policies))->handle(new PublishPolicyVersionCommand(key: 'terms_of_service'));

    $user = legalAdultUser();
    $users->save($user);

    $response = (new RecordConsentHandler($policies, $consents, $users))
        ->handle(new RecordConsentCommand(userId: $user->id(), policyKey: 'terms_of_service'));

    expect($response)->toBeInstanceOf(ConsentResponse::class)
        ->and($response->policyVersion)->toBe(1)
        ->and($response->guardianDeclaration)->toBeNull()
        ->and($consents->findByUserId($user->id()))->toHaveCount(1);
});

it('rechaza registrar consentimiento a una politica inexistente', function (): void {
    $policies = new InMemoryConsentPolicyRepository;
    $consents = new InMemoryUserConsentRepository;
    $users = new InMemoryLegalUserRepository;

    expect(fn () => (new RecordConsentHandler($policies, $consents, $users))
        ->handle(new RecordConsentCommand(userId: (string) Str::uuid(), policyKey: 'inexistente')))
        ->toThrow(PolicyNotFound::class);
});

it('exige la declaracion de un tutor para registrar el consentimiento de un menor', function (): void {
    $policies = new InMemoryConsentPolicyRepository;
    $consents = new InMemoryUserConsentRepository;
    $users = new InMemoryLegalUserRepository;
    (new PublishPolicyVersionHandler($policies))->handle(new PublishPolicyVersionCommand(key: 'privacy_policy'));

    $minor = legalMinorUser();
    $users->save($minor);

    expect(fn () => (new RecordConsentHandler($policies, $consents, $users))
        ->handle(new RecordConsentCommand(userId: $minor->id(), policyKey: 'privacy_policy')))
        ->toThrow(GuardianDeclarationRequired::class);
});

it('registra la declaracion del tutor al aceptar el consentimiento de un menor', function (): void {
    $policies = new InMemoryConsentPolicyRepository;
    $consents = new InMemoryUserConsentRepository;
    $users = new InMemoryLegalUserRepository;
    (new PublishPolicyVersionHandler($policies))->handle(new PublishPolicyVersionCommand(key: 'privacy_policy'));

    $minor = legalMinorUser();
    $users->save($minor);

    $response = (new RecordConsentHandler($policies, $consents, $users))->handle(new RecordConsentCommand(
        userId: $minor->id(),
        policyKey: 'privacy_policy',
        guardianDeclaration: 'María Pérez',
    ));

    expect($response->guardianDeclaration)->toBe('María Pérez');
});

it('lista unicamente la version vigente de cada politica', function (): void {
    $policies = new InMemoryConsentPolicyRepository;
    (new PublishPolicyVersionHandler($policies))->handle(new PublishPolicyVersionCommand(key: 'privacy_policy'));
    (new PublishPolicyVersionHandler($policies))->handle(new PublishPolicyVersionCommand(key: 'privacy_policy'));
    (new PublishPolicyVersionHandler($policies))->handle(new PublishPolicyVersionCommand(key: 'terms_of_service'));

    $responses = (new ListPoliciesHandler($policies))->handle(new ListPoliciesQuery);

    expect($responses)->toHaveCount(2);

    $versions = [];
    foreach ($responses as $response) {
        $versions[$response->key] = $response->version;
    }

    expect($versions['privacy_policy'])->toBe(2)
        ->and($versions['terms_of_service'])->toBe(1);
});

it('lista el historial de consentimientos de un usuario', function (): void {
    $policies = new InMemoryConsentPolicyRepository;
    $consents = new InMemoryUserConsentRepository;
    $users = new InMemoryLegalUserRepository;
    (new PublishPolicyVersionHandler($policies))->handle(new PublishPolicyVersionCommand(key: 'privacy_policy'));

    $user = legalAdultUser();
    $users->save($user);
    (new RecordConsentHandler($policies, $consents, $users))
        ->handle(new RecordConsentCommand(userId: $user->id(), policyKey: 'privacy_policy'));

    $responses = (new GetMyConsentsHandler($consents))->handle(new GetMyConsentsQuery(userId: $user->id()));

    expect($responses)->toHaveCount(1)
        ->and($responses[0])->toBeInstanceOf(ConsentResponse::class)
        ->and($responses[0]->policyKey)->toBe('privacy_policy');
});

it('revoca el consentimiento activo mas reciente de un usuario para una politica', function (): void {
    $policies = new InMemoryConsentPolicyRepository;
    $consents = new InMemoryUserConsentRepository;
    $users = new InMemoryLegalUserRepository;
    (new PublishPolicyVersionHandler($policies))->handle(new PublishPolicyVersionCommand(key: 'privacy_policy'));

    $user = legalAdultUser();
    $users->save($user);
    (new RecordConsentHandler($policies, $consents, $users))
        ->handle(new RecordConsentCommand(userId: $user->id(), policyKey: 'privacy_policy'));

    $response = (new RevokeConsentHandler($consents))
        ->handle(new RevokeConsentCommand(userId: $user->id(), policyKey: 'privacy_policy'));

    expect($response->revokedAt)->not->toBeNull();

    $history = (new GetMyConsentsHandler($consents))->handle(new GetMyConsentsQuery(userId: $user->id()));
    expect($history[0]->revokedAt)->not->toBeNull();
});

it('rechaza revocar cuando no hay ningun consentimiento activo para la politica', function (): void {
    $consents = new InMemoryUserConsentRepository;

    (new RevokeConsentHandler($consents))
        ->handle(new RevokeConsentCommand(userId: (string) Str::uuid(), policyKey: 'privacy_policy'));
})->throws(ConsentNotFound::class);
