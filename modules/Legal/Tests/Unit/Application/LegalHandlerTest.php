<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Legal\Application\Commands\PublishPolicyVersionCommand;
use Modules\Legal\Application\Commands\RecordConsentCommand;
use Modules\Legal\Application\Queries\GetMyConsentsQuery;
use Modules\Legal\Application\Queries\ListPoliciesQuery;
use Modules\Legal\Application\Responses\ConsentResponse;
use Modules\Legal\Application\Responses\PolicyResponse;
use Modules\Legal\Application\UseCases\GetMyConsentsHandler;
use Modules\Legal\Application\UseCases\ListPoliciesHandler;
use Modules\Legal\Application\UseCases\PublishPolicyVersionHandler;
use Modules\Legal\Application\UseCases\RecordConsentHandler;
use Modules\Legal\Domain\Aggregates\ConsentPolicy;
use Modules\Legal\Domain\Entities\UserConsent;
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

it('registra el consentimiento de un usuario a la version vigente', function (): void {
    $policies = new InMemoryConsentPolicyRepository;
    $consents = new InMemoryUserConsentRepository;
    (new PublishPolicyVersionHandler($policies))->handle(new PublishPolicyVersionCommand(key: 'terms_of_service'));

    $userId = (string) Str::uuid();
    $response = (new RecordConsentHandler($policies, $consents))
        ->handle(new RecordConsentCommand(userId: $userId, policyKey: 'terms_of_service'));

    expect($response)->toBeInstanceOf(ConsentResponse::class)
        ->and($response->policyVersion)->toBe(1)
        ->and($consents->findByUserId($userId))->toHaveCount(1);
});

it('rechaza registrar consentimiento a una politica inexistente', function (): void {
    $policies = new InMemoryConsentPolicyRepository;
    $consents = new InMemoryUserConsentRepository;

    expect(fn () => (new RecordConsentHandler($policies, $consents))
        ->handle(new RecordConsentCommand(userId: (string) Str::uuid(), policyKey: 'inexistente')))
        ->toThrow(PolicyNotFound::class);
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
    (new PublishPolicyVersionHandler($policies))->handle(new PublishPolicyVersionCommand(key: 'privacy_policy'));

    $userId = (string) Str::uuid();
    (new RecordConsentHandler($policies, $consents))
        ->handle(new RecordConsentCommand(userId: $userId, policyKey: 'privacy_policy'));

    $responses = (new GetMyConsentsHandler($consents))->handle(new GetMyConsentsQuery(userId: $userId));

    expect($responses)->toHaveCount(1)
        ->and($responses[0])->toBeInstanceOf(ConsentResponse::class)
        ->and($responses[0]->policyKey)->toBe('privacy_policy');
});
