<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Webhook\Domain\Aggregates\WebhookSubscription;
use Modules\Webhook\Domain\Entities\WebhookDelivery;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Repositories\WebhookDeliveryRepository;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookSigningSecret;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedWebhookWebUser(): UserModel
{
    $user = User::register(id: (string) Str::uuid(), name: 'Usuario sin permiso webhook', email: Email::fromString(Str::uuid().'@edudrive.cr'), passwordHash: 'hash');
    app(UserRepository::class)->save($user);

    return UserModel::query()->findOrFail($user->id());
}

function persistedWebhookWebSubscription(): WebhookSubscription
{
    $subscription = WebhookSubscription::register(
        id: WebhookSubscriptionId::fromString((string) Str::uuid()),
        url: 'https://partner.example.test/events',
        events: [WebhookEventName::EnrollmentCreated],
        secret: WebhookSigningSecret::generate(),
    );
    app(WebhookSubscriptionRepository::class)->save($subscription);

    return $subscription;
}

it('requiere autenticación y permiso para consultar webhooks', function (): void {
    /** @var TestCase $this */
    $this->get('/admin/webhooks')->assertRedirect(route('login'));
    $this->actingAs(persistedWebhookWebUser(), 'web');
    $this->get('/admin/webhooks')->assertForbidden();
});

it('registra una suscripción y muestra el secreto temporalmente', function (): void {
    /** @var TestCase $this */
    $this->actingAs(actingAsSuperAdminUser(), 'web');

    $this->get('/admin/webhooks')
        ->assertOk()
        ->assertSeeText('Nueva matrícula creada')
        ->assertSeeText('Nuevo certificado emitido')
        ->assertDontSeeText('Enrollment Created');

    $this->post('/admin/webhooks', [
        'url' => 'https://partner.example.test/events',
        'events' => ['enrollment.created', 'certificate.issued'],
    ])
        ->assertRedirect()
        ->assertSessionHas('status')
        ->assertSessionHas('webhook_secret', fn (mixed $secret): bool => is_string($secret) && $secret !== '');

    expect(app(WebhookSubscriptionRepository::class)->all())->toHaveCount(1);
});

it('administra el estado, el secreto y las entregas', function (): void {
    /** @var TestCase $this */
    $subscription = persistedWebhookWebSubscription();
    $delivery = WebhookDelivery::create((string) Str::uuid(), $subscription->id()->value(), WebhookEventName::EnrollmentCreated, []);
    $delivery->recordFailedAttempt(500, 'error', new DateTimeImmutable('now'));
    app(WebhookDeliveryRepository::class)->save($delivery);
    $this->actingAs(actingAsSuperAdminUser(), 'web');

    $this->get('/admin/webhooks')->assertOk()->assertSeeText('partner.example.test');
    $this->get('/admin/webhooks/'.$subscription->id()->value().'/entregas')->assertOk()->assertSeeText('Nueva matrícula creada')->assertSeeText('Falló');
    $this->post('/admin/webhooks/'.$subscription->id()->value().'/suspender')->assertRedirect()->assertSessionHas('status');
    $this->post('/admin/webhooks/'.$subscription->id()->value().'/reactivar')->assertRedirect()->assertSessionHas('status');
    $this->post('/admin/webhooks/'.$subscription->id()->value().'/rotar-secreto')->assertRedirect()->assertSessionHas('webhook_secret');

    Http::fake(['partner.example.test/*' => Http::response('ok', 200)]);
    $this->post('/admin/webhooks/entregas/'.$delivery->id().'/reintentar')->assertRedirect()->assertSessionHas('status');
});
