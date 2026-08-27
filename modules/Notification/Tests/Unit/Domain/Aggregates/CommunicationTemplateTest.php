<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Notification\Domain\Aggregates\CommunicationTemplate;
use Modules\Notification\Domain\Enums\CommunicationTemplateStatus;
use Modules\Notification\Domain\Exceptions\InvalidCommunicationTemplateTransition;
use Modules\Notification\Domain\Exceptions\MissingTemplateVariable;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateCode;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateId;
use Modules\Notification\Domain\ValueObjects\RenderedTemplate;

function newCommunicationTemplate(): CommunicationTemplate
{
    return CommunicationTemplate::create(
        id: CommunicationTemplateId::fromString((string) Str::uuid()),
        code: CommunicationTemplateCode::fromString('bienvenida-email'),
        locale: 'es',
        subjectTemplate: 'Bienvenido a {{institution_name}}, {{student_name}}',
        bodyTemplate: 'Hola {{student_name}}, tu curso {{course_name}} ha comenzado en {{institution_name}}.',
        variables: ['institution_name', 'student_name', 'course_name'],
        registeredAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('se crea activa en version uno', function (): void {
    $template = newCommunicationTemplate();

    expect($template->status())->toBe(CommunicationTemplateStatus::Active)
        ->and($template->version())->toBe(1)
        ->and($template->locale())->toBe('es')
        ->and($template->retiredAt())->toBeNull()
        ->and($template->retiredReason())->toBeNull();
});

it('rechaza un idioma con formato invalido', function (): void {
    expect(fn () => CommunicationTemplate::create(
        id: CommunicationTemplateId::fromString((string) Str::uuid()),
        code: CommunicationTemplateCode::fromString('bienvenida-email'),
        locale: 'espanol',
        subjectTemplate: 'Asunto',
        bodyTemplate: 'Cuerpo',
        variables: [],
    ))->toThrow(InvalidArgumentException::class);
});

it('renderiza sustituyendo todas las variables declaradas', function (): void {
    $template = newCommunicationTemplate();

    $rendered = $template->render([
        'institution_name' => 'EDUDRIVE Academy',
        'student_name' => 'Ana',
        'course_name' => 'Manejo defensivo',
    ]);

    expect($rendered)->toBeInstanceOf(RenderedTemplate::class)
        ->and($rendered->subject)->toBe('Bienvenido a EDUDRIVE Academy, Ana')
        ->and($rendered->body)->toBe('Hola Ana, tu curso Manejo defensivo ha comenzado en EDUDRIVE Academy.');
});

it('rechaza renderizar cuando falta una variable declarada', function (): void {
    $template = newCommunicationTemplate();

    expect(fn () => $template->render(['institution_name' => 'EDUDRIVE Academy', 'student_name' => 'Ana']))
        ->toThrow(MissingTemplateVariable::class);
});

it('deja intacto un placeholder no declarado como variable', function (): void {
    $template = CommunicationTemplate::create(
        id: CommunicationTemplateId::fromString((string) Str::uuid()),
        code: CommunicationTemplateCode::fromString('reto'),
        locale: 'es',
        subjectTemplate: 'Asunto',
        bodyTemplate: 'Hola {{student_name}}, revisa {{undeclared_placeholder}}.',
        variables: ['student_name'],
    );

    $rendered = $template->render(['student_name' => 'Ana']);

    expect($rendered->body)->toBe('Hola Ana, revisa {{undeclared_placeholder}}.');
});

it('incrementa la version al actualizar el contenido', function (): void {
    $template = newCommunicationTemplate();

    $template->updateContent(
        subjectTemplate: 'Asunto actualizado',
        bodyTemplate: 'Cuerpo actualizado {{student_name}}',
        variables: ['student_name'],
    );

    expect($template->version())->toBe(2)
        ->and($template->subjectTemplate())->toBe('Asunto actualizado')
        ->and($template->variables())->toBe(['student_name']);
});

it('rechaza actualizar el contenido de una plantilla retirada', function (): void {
    $template = newCommunicationTemplate();
    $template->retire(null, new DateTimeImmutable('now'));

    expect(fn () => $template->updateContent('Asunto', 'Cuerpo', []))
        ->toThrow(InvalidCommunicationTemplateTransition::class);
});

it('se retira y registra el motivo y la fecha', function (): void {
    $template = newCommunicationTemplate();

    $template->retire('Reemplazada por una plantilla mejor definida', new DateTimeImmutable('2026-08-27T00:00:00+00:00'));

    expect($template->status())->toBe(CommunicationTemplateStatus::Retired)
        ->and($template->retiredReason())->toBe('Reemplazada por una plantilla mejor definida')
        ->and($template->retiredAt())->not->toBeNull();
});

it('rechaza retirar una plantilla ya retirada', function (): void {
    $template = newCommunicationTemplate();
    $template->retire(null, new DateTimeImmutable('now'));

    expect(fn () => $template->retire(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidCommunicationTemplateTransition::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = CommunicationTemplateId::fromString((string) Str::uuid());
    $code = CommunicationTemplateCode::fromString('bienvenida-email');
    $registeredAt = new DateTimeImmutable('2026-08-26T10:00:00+00:00');
    $retiredAt = new DateTimeImmutable('2026-08-27T00:00:00+00:00');

    $template = CommunicationTemplate::restore(
        id: $id,
        code: $code,
        locale: 'en',
        subjectTemplate: 'Welcome',
        bodyTemplate: 'Hello {{student_name}}',
        variables: ['student_name'],
        version: 3,
        status: CommunicationTemplateStatus::Retired,
        registeredAt: $registeredAt,
        retiredAt: $retiredAt,
        retiredReason: 'Motivo',
    );

    expect($template->id()->equals($id))->toBeTrue()
        ->and($template->code()->equals($code))->toBeTrue()
        ->and($template->locale())->toBe('en')
        ->and($template->subjectTemplate())->toBe('Welcome')
        ->and($template->bodyTemplate())->toBe('Hello {{student_name}}')
        ->and($template->variables())->toBe(['student_name'])
        ->and($template->version())->toBe(3)
        ->and($template->status())->toBe(CommunicationTemplateStatus::Retired)
        ->and($template->registeredAt())->toBe($registeredAt)
        ->and($template->retiredAt())->toBe($retiredAt)
        ->and($template->retiredReason())->toBe('Motivo');
});
