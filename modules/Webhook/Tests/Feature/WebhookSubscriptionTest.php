<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Webhook\Domain\Aggregates\WebhookSubscription;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookSigningSecret;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedWebhookSubscriptionFeature(): WebhookSubscription
{
    $subscription = WebhookSubscription::register(
        id: WebhookSubscriptionId::fromString((string) Str::uuid()),
        url: 'https://example.test/webhooks',
        events: [WebhookEventName::EnrollmentCreated],
        secret: WebhookSigningSecret::generate(),
    );
    app(WebhookSubscriptionRepository::class)->save($subscription);

    return $subscription;
}

it('registra una suscripcion con el permiso webhooks.manage y devuelve el secreto en texto plano', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/webhooks/subscriptions', [
        'url' => 'https://example.test/webhooks',
        'events' => ['enrollment.created'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.url', 'https://example.test/webhooks')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.events', ['enrollment.created'])
        ->assertJson(fn ($json) => $json->where('data.secret', fn ($value) => is_string($value) && strlen($value) === 64)->etc());
});

it('rechaza registrar una suscripcion sin el permiso webhooks.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->postJson('/api/v1/webhooks/subscriptions', [
        'url' => 'https://example.test/webhooks',
        'events' => ['enrollment.created'],
    ])->assertForbidden();
});

it('rechaza registrar una suscripcion con un nombre de evento invalido', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/webhooks/subscriptions', [
        'url' => 'https://example.test/webhooks',
        'events' => ['no.es.un.evento'],
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_WEBHOOK_EVENT_NAME');
});

it('audita el registro de una suscripcion con el id de quien lo realiza', function (): void {
    /** @var TestCase $this */
    $actor = actingAsSuperAdminUser();

    $this->postJson('/api/v1/webhooks/subscriptions', [
        'url' => 'https://example.test/webhooks',
        'events' => ['enrollment.created'],
    ])->assertCreated();

    $entry = AuditLogModel::query()->where('action', 'webhook.subscription_registered')->latest('occurred_at')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->user_id)->toBe($actor->id);
});

it('lista las suscripciones con el permiso webhooks.view sin exponer el secreto', function (): void {
    /** @var TestCase $this */
    $subscription = persistedWebhookSubscriptionFeature();
    actingAsRole(Role::SuperAdmin);

    $this->getJson('/api/v1/webhooks/subscriptions')
        ->assertOk()
        ->assertJsonPath('data.0.id', $subscription->id()->value())
        ->assertJsonMissingPath('data.0.secret');
});

it('consulta una suscripcion por id sin exponer el secreto', function (): void {
    /** @var TestCase $this */
    $subscription = persistedWebhookSubscriptionFeature();
    actingAsRole(Role::SuperAdmin);

    $this->getJson("/api/v1/webhooks/subscriptions/{$subscription->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $subscription->id()->value())
        ->assertJsonMissingPath('data.secret');
});

it('rechaza listar y consultar suscripciones sin el permiso webhooks.view', function (): void {
    /** @var TestCase $this */
    $subscription = persistedWebhookSubscriptionFeature();
    actingAsRole(Role::InstitutionalAdmin);

    $this->getJson('/api/v1/webhooks/subscriptions')->assertForbidden();
    $this->getJson("/api/v1/webhooks/subscriptions/{$subscription->id()->value()}")->assertForbidden();
});

it('suspende y reactiva una suscripcion con el permiso webhooks.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $subscription = persistedWebhookSubscriptionFeature();
    $id = $subscription->id()->value();

    $this->postJson("/api/v1/webhooks/subscriptions/{$id}/suspend")
        ->assertOk()
        ->assertJsonPath('data.status', 'suspended');

    $this->postJson("/api/v1/webhooks/subscriptions/{$id}/reactivate")
        ->assertOk()
        ->assertJsonPath('data.status', 'active');
});

it('responde 422 ante una transicion invalida', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $subscription = persistedWebhookSubscriptionFeature();

    $this->postJson("/api/v1/webhooks/subscriptions/{$subscription->id()->value()}/reactivate")
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_WEBHOOK_SUBSCRIPTION_TRANSITION');
});

it('rota el secreto y devuelve el nuevo valor en texto plano', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $subscription = persistedWebhookSubscriptionFeature();

    $this->postJson("/api/v1/webhooks/subscriptions/{$subscription->id()->value()}/rotate-secret")
        ->assertOk()
        ->assertJson(fn ($json) => $json->where('data.secret', fn ($value) => is_string($value) && strlen($value) === 64)->etc());
});

it('rechaza mutar una suscripcion sin el permiso webhooks.manage', function (): void {
    /** @var TestCase $this */
    $subscription = persistedWebhookSubscriptionFeature();
    actingAsRole(Role::InstitutionalAdmin);
    $id = $subscription->id()->value();

    $this->postJson("/api/v1/webhooks/subscriptions/{$id}/suspend")->assertForbidden();
    $this->postJson("/api/v1/webhooks/subscriptions/{$id}/reactivate")->assertForbidden();
    $this->postJson("/api/v1/webhooks/subscriptions/{$id}/rotate-secret")->assertForbidden();
});

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    $subscription = persistedWebhookSubscriptionFeature();

    $this->getJson('/api/v1/webhooks/subscriptions')->assertUnauthorized();
    $this->getJson("/api/v1/webhooks/subscriptions/{$subscription->id()->value()}")->assertUnauthorized();
    $this->postJson('/api/v1/webhooks/subscriptions', ['url' => 'https://example.test', 'events' => ['enrollment.created']])->assertUnauthorized();
});
