<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Notification\Domain\Aggregates\CommunicationTemplate;
use Modules\Notification\Domain\Repositories\CommunicationTemplateRepository;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateCode;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedCommunicationTemplateFeature(?string $code = null, string $locale = 'es'): CommunicationTemplate
{
    $template = CommunicationTemplate::create(
        id: CommunicationTemplateId::fromString((string) Str::uuid()),
        code: CommunicationTemplateCode::fromString($code ?? 'PLANTILLA-'.strtoupper((string) Str::random(6))),
        locale: $locale,
        subjectTemplate: 'Bienvenido {{student_name}}',
        bodyTemplate: 'Hola {{student_name}}, bienvenido a {{institution_name}}.',
        variables: ['student_name', 'institution_name'],
    );
    app(CommunicationTemplateRepository::class)->save($template);

    return $template;
}

it('crea una plantilla con el permiso communication_templates.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/notification/templates', [
        'code' => 'bienvenida-email',
        'locale' => 'es',
        'subject_template' => 'Bienvenido {{student_name}}',
        'body_template' => 'Hola {{student_name}}',
        'variables' => ['student_name'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'BIENVENIDA-EMAIL')
        ->assertJsonPath('data.version', 1)
        ->assertJsonPath('data.status', 'active');
});

it('rechaza crear una plantilla sin el permiso communication_templates.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->postJson('/api/v1/notification/templates', [
        'code' => 'bienvenida-email',
        'locale' => 'es',
        'subject_template' => 'Asunto',
        'body_template' => 'Cuerpo',
        'variables' => [],
    ])->assertForbidden();
});

it('rechaza crear una plantilla con el mismo codigo e idioma', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $template = persistedCommunicationTemplateFeature();

    $this->postJson('/api/v1/notification/templates', [
        'code' => $template->code()->value(),
        'locale' => $template->locale(),
        'subject_template' => 'Otro asunto',
        'body_template' => 'Otro cuerpo',
        'variables' => [],
    ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'COMMUNICATION_TEMPLATE_ALREADY_EXISTS');
});

it('lista el catalogo de plantillas con el permiso communication_templates.view', function (): void {
    /** @var TestCase $this */
    $template = persistedCommunicationTemplateFeature();
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/notification/templates')
        ->assertOk()
        ->assertJsonPath('data.0.id', $template->id()->value());
});

it('rechaza listar el catalogo de plantillas para un estudiante', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/notification/templates')->assertForbidden();
});

it('consulta una plantilla por id', function (): void {
    /** @var TestCase $this */
    $template = persistedCommunicationTemplateFeature();
    actingAsRole(Role::Teacher);

    $this->getJson("/api/v1/notification/templates/{$template->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $template->id()->value());
});

it('actualiza el contenido de una plantilla e incrementa su version', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $template = persistedCommunicationTemplateFeature();

    $this->putJson("/api/v1/notification/templates/{$template->id()->value()}", [
        'subject_template' => 'Asunto nuevo',
        'body_template' => 'Cuerpo nuevo',
        'variables' => [],
    ])
        ->assertOk()
        ->assertJsonPath('data.subject_template', 'Asunto nuevo')
        ->assertJsonPath('data.version', 2);
});

it('rechaza actualizar una plantilla sin el permiso communication_templates.manage', function (): void {
    /** @var TestCase $this */
    $template = persistedCommunicationTemplateFeature();
    actingAsRole(Role::Teacher);

    $this->putJson("/api/v1/notification/templates/{$template->id()->value()}", [
        'subject_template' => 'Asunto',
        'body_template' => 'Cuerpo',
        'variables' => [],
    ])->assertForbidden();
});

it('retira una plantilla con el permiso communication_templates.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $template = persistedCommunicationTemplateFeature();

    $this->postJson("/api/v1/notification/templates/{$template->id()->value()}/retire", ['reason' => 'Ya no aplica'])
        ->assertOk()
        ->assertJsonPath('data.status', 'retired');
});

it('rechaza retirar una plantilla sin el permiso communication_templates.manage', function (): void {
    /** @var TestCase $this */
    $template = persistedCommunicationTemplateFeature();
    actingAsRole(Role::Teacher);

    $this->postJson("/api/v1/notification/templates/{$template->id()->value()}/retire")
        ->assertForbidden();
});

it('previsualiza una plantilla sustituyendo sus variables', function (): void {
    /** @var TestCase $this */
    $template = persistedCommunicationTemplateFeature();
    actingAsRole(Role::Teacher);

    $this->postJson("/api/v1/notification/templates/{$template->id()->value()}/preview", [
        'variables' => ['student_name' => 'Ana', 'institution_name' => 'EDUDRIVE Academy'],
    ])
        ->assertOk()
        ->assertJsonPath('data.subject', 'Bienvenido Ana')
        ->assertJsonPath('data.body', 'Hola Ana, bienvenido a EDUDRIVE Academy.');
});

it('rechaza previsualizar cuando falta una variable declarada', function (): void {
    /** @var TestCase $this */
    $template = persistedCommunicationTemplateFeature();
    actingAsRole(Role::Teacher);

    $this->postJson("/api/v1/notification/templates/{$template->id()->value()}/preview", [
        'variables' => ['student_name' => 'Ana'],
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'MISSING_TEMPLATE_VARIABLE');
});

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    $template = persistedCommunicationTemplateFeature();

    $this->getJson('/api/v1/notification/templates')->assertUnauthorized();
    $this->getJson("/api/v1/notification/templates/{$template->id()->value()}")->assertUnauthorized();
    $this->postJson('/api/v1/notification/templates', [])->assertUnauthorized();
    $this->putJson("/api/v1/notification/templates/{$template->id()->value()}", [])->assertUnauthorized();
    $this->postJson("/api/v1/notification/templates/{$template->id()->value()}/retire")->assertUnauthorized();
    $this->postJson("/api/v1/notification/templates/{$template->id()->value()}/preview", ['variables' => []])->assertUnauthorized();
});
