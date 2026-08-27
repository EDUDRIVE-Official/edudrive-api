<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Notification\Domain\Aggregates\CommunicationTemplate;
use Modules\Notification\Domain\Enums\CommunicationTemplateStatus;
use Modules\Notification\Domain\Repositories\CommunicationTemplateRepository;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateCode;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateId;

uses(RefreshDatabase::class);

function newPersistableCommunicationTemplate(?string $code = null, string $locale = 'es'): CommunicationTemplate
{
    return CommunicationTemplate::create(
        id: CommunicationTemplateId::fromString((string) Str::uuid()),
        code: CommunicationTemplateCode::fromString($code ?? 'PLANTILLA-'.strtoupper((string) Str::random(6))),
        locale: $locale,
        subjectTemplate: 'Bienvenido {{student_name}}',
        bodyTemplate: 'Hola {{student_name}}, bienvenido a {{institution_name}}.',
        variables: ['student_name', 'institution_name'],
        registeredAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('guarda y recupera una plantilla por identificador', function (): void {
    $template = newPersistableCommunicationTemplate();

    app(CommunicationTemplateRepository::class)->save($template);
    $found = app(CommunicationTemplateRepository::class)->findById($template->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($template->id()))->toBeTrue()
        ->and($found?->code()->equals($template->code()))->toBeTrue()
        ->and($found?->locale())->toBe('es')
        ->and($found?->variables())->toBe(['student_name', 'institution_name'])
        ->and($found?->version())->toBe(1)
        ->and($found?->status())->toBe(CommunicationTemplateStatus::Active);
});

it('guarda y recupera una plantilla editada con su version incrementada', function (): void {
    $template = newPersistableCommunicationTemplate();
    $template->updateContent('Asunto nuevo', 'Cuerpo nuevo', []);

    app(CommunicationTemplateRepository::class)->save($template);
    $found = app(CommunicationTemplateRepository::class)->findById($template->id());

    expect($found?->version())->toBe(2)
        ->and($found?->subjectTemplate())->toBe('Asunto nuevo');
});

it('guarda y recupera una plantilla retirada con su motivo', function (): void {
    $template = newPersistableCommunicationTemplate();
    $template->retire('Motivo de retiro', new DateTimeImmutable('2026-08-27T00:00:00+00:00'));

    app(CommunicationTemplateRepository::class)->save($template);
    $found = app(CommunicationTemplateRepository::class)->findById($template->id());

    expect($found?->status())->toBe(CommunicationTemplateStatus::Retired)
        ->and($found?->retiredReason())->toBe('Motivo de retiro');
});

it('encuentra una plantilla por su codigo e idioma', function (): void {
    $templateEs = newPersistableCommunicationTemplate('PLANTILLA-UNICA-001', 'es');
    $templateEn = newPersistableCommunicationTemplate('PLANTILLA-UNICA-001', 'en');
    $repository = app(CommunicationTemplateRepository::class);
    $repository->save($templateEs);
    $repository->save($templateEn);

    $foundEs = $repository->findByCodeAndLocale(CommunicationTemplateCode::fromString('plantilla-unica-001'), 'es');
    $foundEn = $repository->findByCodeAndLocale(CommunicationTemplateCode::fromString('plantilla-unica-001'), 'en');

    expect($foundEs?->id()->equals($templateEs->id()))->toBeTrue()
        ->and($foundEn?->id()->equals($templateEn->id()))->toBeTrue();
});

it('lista todas las plantillas registradas', function (): void {
    $repository = app(CommunicationTemplateRepository::class);
    $repository->save(newPersistableCommunicationTemplate());
    $repository->save(newPersistableCommunicationTemplate());

    expect($repository->all())->toHaveCount(2);
});
