<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Legal\Domain\Aggregates\ConsentPolicy;
use Modules\Legal\Domain\Repositories\ConsentPolicyRepository;
use Modules\Legal\Domain\Repositories\UserConsentRepository;
use Modules\Legal\Domain\ValueObjects\PolicyKey;
use Tests\TestCase;

function persistedLegalWebUser(?DateTimeImmutable $birthDate = null): User
{
    $user = User::register(id: (string) Str::uuid(), name: 'Usuario Legal', email: Email::fromString(Str::uuid().'@edudrive.cr'), passwordHash: 'hash', dateOfBirth: $birthDate);
    app(UserRepository::class)->save($user);

    return $user;
}

function persistedLegalWebPolicy(string $key = 'privacy_policy'): ConsentPolicy
{
    $policy = ConsentPolicy::publish(id: (string) Str::uuid(), key: PolicyKey::fromString($key), version: 1);
    app(ConsentPolicyRepository::class)->save($policy);

    return $policy;
}

it('requiere autenticación para administrar consentimientos propios', function (): void {
    /** @var TestCase $this */
    $this->get('/mis-consentimientos')->assertRedirect(route('login'));
});

it('permite aceptar y revocar una política vigente', function (): void {
    /** @var TestCase $this */
    $user = persistedLegalWebUser();
    persistedLegalWebPolicy();
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/mis-consentimientos')->assertOk()->assertSeeText('Política de privacidad')->assertSeeText('Pendiente');
    $this->post('/mis-consentimientos', ['policy_key' => 'privacy_policy'])->assertRedirect()->assertSessionHas('status');
    expect(app(UserConsentRepository::class)->findLatestActiveByUserAndPolicy($user->id(), PolicyKey::fromString('privacy_policy')))->not->toBeNull();
    $this->delete('/mis-consentimientos/privacy_policy')->assertRedirect()->assertSessionHas('status');
    expect(app(UserConsentRepository::class)->findLatestActiveByUserAndPolicy($user->id(), PolicyKey::fromString('privacy_policy')))->toBeNull();
});

it('exige declaración de tutor cuando el usuario es menor', function (): void {
    /** @var TestCase $this */
    $user = persistedLegalWebUser(new DateTimeImmutable('-15 years'));
    persistedLegalWebPolicy();
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->post('/mis-consentimientos', ['policy_key' => 'privacy_policy'])
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('protege las pantallas administrativas mediante permisos', function (): void {
    /** @var TestCase $this */
    $user = persistedLegalWebUser();
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');
    $this->get('/admin/politicas-legales')->assertForbidden();
    $this->get('/admin/consentimientos-menores')->assertForbidden();

    $this->actingAs(actingAsSuperAdminUser(), 'web');
    $this->get('/admin/politicas-legales')
        ->assertOk()
        ->assertSeeText('Selecciona una política')
        ->assertDontSeeText('Clave de política');
    $this->post('/admin/politicas-legales', ['key' => 'terms_of_service'])->assertRedirect()->assertSessionHas('status');
    $this->get('/admin/consentimientos-menores?organization_id='.Str::uuid())
        ->assertOk()
        ->assertSeeText('Selecciona una organización')
        ->assertDontSeeText('Identificador de organización');
});
