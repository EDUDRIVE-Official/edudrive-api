<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Notification\Application\Commands\CreateCommunicationTemplateCommand;
use Modules\Notification\Application\Commands\RetireCommunicationTemplateCommand;
use Modules\Notification\Application\Commands\UpdateCommunicationTemplateCommand;
use Modules\Notification\Application\Exceptions\CommunicationTemplateAlreadyExists;
use Modules\Notification\Application\Exceptions\CommunicationTemplateNotFound;
use Modules\Notification\Application\Queries\GetCommunicationTemplateQuery;
use Modules\Notification\Application\Queries\ListCommunicationTemplatesQuery;
use Modules\Notification\Application\Queries\PreviewCommunicationTemplateQuery;
use Modules\Notification\Application\Responses\CommunicationTemplateResponse;
use Modules\Notification\Application\UseCases\CreateCommunicationTemplateHandler;
use Modules\Notification\Application\UseCases\GetCommunicationTemplateHandler;
use Modules\Notification\Application\UseCases\ListCommunicationTemplatesHandler;
use Modules\Notification\Application\UseCases\PreviewCommunicationTemplateHandler;
use Modules\Notification\Application\UseCases\RetireCommunicationTemplateHandler;
use Modules\Notification\Application\UseCases\UpdateCommunicationTemplateHandler;
use Modules\Notification\Domain\Aggregates\CommunicationTemplate;
use Modules\Notification\Domain\Exceptions\InvalidCommunicationTemplateTransition;
use Modules\Notification\Domain\Exceptions\MissingTemplateVariable;
use Modules\Notification\Domain\Repositories\CommunicationTemplateRepository;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateCode;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateId;

final class InMemoryCommunicationTemplateRepository implements CommunicationTemplateRepository
{
    /** @var array<string, CommunicationTemplate> */
    public array $items = [];

    public function save(CommunicationTemplate $template): void
    {
        $this->items[$template->id()->value()] = $template;
    }

    public function findById(CommunicationTemplateId $id): ?CommunicationTemplate
    {
        return $this->items[$id->value()] ?? null;
    }

    public function findByCodeAndLocale(CommunicationTemplateCode $code, string $locale): ?CommunicationTemplate
    {
        foreach ($this->items as $template) {
            if ($template->code()->equals($code) && $template->locale() === $locale) {
                return $template;
            }
        }

        return null;
    }

    /** @return list<CommunicationTemplate> */
    public function all(): array
    {
        return array_values($this->items);
    }
}

function persistedCommunicationTemplateFor(
    InMemoryCommunicationTemplateRepository $repository,
    ?string $code = null,
    string $locale = 'es',
): CommunicationTemplate {
    $template = CommunicationTemplate::create(
        id: CommunicationTemplateId::fromString((string) Str::uuid()),
        code: CommunicationTemplateCode::fromString($code ?? 'PLANTILLA-'.strtoupper((string) Str::random(6))),
        locale: $locale,
        subjectTemplate: 'Bienvenido {{student_name}}',
        bodyTemplate: 'Hola {{student_name}}, bienvenido a {{institution_name}}.',
        variables: ['student_name', 'institution_name'],
    );
    $repository->save($template);

    return $template;
}

it('crea una plantilla nueva', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;

    $response = (new CreateCommunicationTemplateHandler($templates))->handle(new CreateCommunicationTemplateCommand(
        code: 'bienvenida-email',
        locale: 'es',
        subjectTemplate: 'Bienvenido {{student_name}}',
        bodyTemplate: 'Hola {{student_name}}',
        variables: ['student_name'],
    ));

    expect($response)->toBeInstanceOf(CommunicationTemplateResponse::class)
        ->and($response->code)->toBe('BIENVENIDA-EMAIL')
        ->and($response->version)->toBe(1)
        ->and($response->status)->toBe('active');
});

it('rechaza crear una plantilla con el mismo codigo e idioma', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;
    $template = persistedCommunicationTemplateFor($templates);

    expect(fn () => (new CreateCommunicationTemplateHandler($templates))->handle(new CreateCommunicationTemplateCommand(
        code: $template->code()->value(),
        locale: $template->locale(),
        subjectTemplate: 'Otro asunto',
        bodyTemplate: 'Otro cuerpo',
        variables: [],
    )))->toThrow(CommunicationTemplateAlreadyExists::class);
});

it('permite el mismo codigo en un idioma distinto', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;
    $template = persistedCommunicationTemplateFor($templates, 'bienvenida-email', 'es');

    $response = (new CreateCommunicationTemplateHandler($templates))->handle(new CreateCommunicationTemplateCommand(
        code: $template->code()->value(),
        locale: 'en',
        subjectTemplate: 'Welcome',
        bodyTemplate: 'Hello',
        variables: [],
    ));

    expect($response->locale)->toBe('en');
});

it('actualiza el contenido de una plantilla e incrementa su version', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;
    $template = persistedCommunicationTemplateFor($templates);

    $response = (new UpdateCommunicationTemplateHandler($templates))->handle(new UpdateCommunicationTemplateCommand(
        templateId: $template->id()->value(),
        subjectTemplate: 'Asunto nuevo',
        bodyTemplate: 'Cuerpo nuevo',
        variables: [],
    ));

    expect($response->version)->toBe(2)
        ->and($response->subjectTemplate)->toBe('Asunto nuevo');
});

it('rechaza actualizar una plantilla inexistente', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;

    expect(fn () => (new UpdateCommunicationTemplateHandler($templates))->handle(new UpdateCommunicationTemplateCommand((string) Str::uuid(), 'Asunto', 'Cuerpo', [])))
        ->toThrow(CommunicationTemplateNotFound::class);
});

it('rechaza actualizar el contenido de una plantilla retirada', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;
    $template = persistedCommunicationTemplateFor($templates);
    (new RetireCommunicationTemplateHandler($templates))->handle(new RetireCommunicationTemplateCommand($template->id()->value()));

    expect(fn () => (new UpdateCommunicationTemplateHandler($templates))->handle(new UpdateCommunicationTemplateCommand($template->id()->value(), 'Asunto', 'Cuerpo', [])))
        ->toThrow(InvalidCommunicationTemplateTransition::class);
});

it('retira una plantilla existente', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;
    $template = persistedCommunicationTemplateFor($templates);

    $response = (new RetireCommunicationTemplateHandler($templates))->handle(new RetireCommunicationTemplateCommand($template->id()->value(), 'Ya no aplica'));

    expect($response->status)->toBe('retired');
});

it('rechaza retirar una plantilla inexistente', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;

    expect(fn () => (new RetireCommunicationTemplateHandler($templates))->handle(new RetireCommunicationTemplateCommand((string) Str::uuid())))
        ->toThrow(CommunicationTemplateNotFound::class);
});

it('consulta una plantilla por id', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;
    $template = persistedCommunicationTemplateFor($templates);

    $response = (new GetCommunicationTemplateHandler($templates))->handle(new GetCommunicationTemplateQuery($template->id()->value()));

    expect($response->id)->toBe($template->id()->value());
});

it('rechaza consultar una plantilla inexistente', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;

    expect(fn () => (new GetCommunicationTemplateHandler($templates))->handle(new GetCommunicationTemplateQuery((string) Str::uuid())))
        ->toThrow(CommunicationTemplateNotFound::class);
});

it('lista todas las plantillas del catalogo', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;
    persistedCommunicationTemplateFor($templates);
    persistedCommunicationTemplateFor($templates);

    $responses = (new ListCommunicationTemplatesHandler($templates))->handle(new ListCommunicationTemplatesQuery);

    expect($responses)->toHaveCount(2)
        ->and($responses[0])->toBeInstanceOf(CommunicationTemplateResponse::class);
});

it('previsualiza una plantilla sustituyendo sus variables', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;
    $template = persistedCommunicationTemplateFor($templates);

    $response = (new PreviewCommunicationTemplateHandler($templates))->handle(new PreviewCommunicationTemplateQuery(
        templateId: $template->id()->value(),
        variables: ['student_name' => 'Ana', 'institution_name' => 'EDUDRIVE Academy'],
    ));

    expect($response->subject)->toBe('Bienvenido Ana')
        ->and($response->body)->toBe('Hola Ana, bienvenido a EDUDRIVE Academy.');
});

it('rechaza previsualizar cuando falta una variable declarada', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;
    $template = persistedCommunicationTemplateFor($templates);

    expect(fn () => (new PreviewCommunicationTemplateHandler($templates))->handle(new PreviewCommunicationTemplateQuery($template->id()->value(), ['student_name' => 'Ana'])))
        ->toThrow(MissingTemplateVariable::class);
});

it('rechaza previsualizar una plantilla inexistente', function (): void {
    $templates = new InMemoryCommunicationTemplateRepository;

    expect(fn () => (new PreviewCommunicationTemplateHandler($templates))->handle(new PreviewCommunicationTemplateQuery((string) Str::uuid(), [])))
        ->toThrow(CommunicationTemplateNotFound::class);
});
