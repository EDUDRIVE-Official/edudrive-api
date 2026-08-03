<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CreateProgramCommand;
use Modules\Academic\Application\Exceptions\ProgramCodeAlreadyExists;
use Modules\Academic\Application\Queries\ListProgramsQuery;
use Modules\Academic\Application\UseCases\CreateProgramHandler;
use Modules\Academic\Application\UseCases\ListProgramsHandler;
use Modules\Academic\Domain\Aggregates\EducationalProgram;
use Modules\Academic\Domain\Repositories\ProgramRepository;
use Modules\Academic\Domain\ValueObjects\ProgramCode;
use Modules\Academic\Domain\ValueObjects\ProgramId;

function inMemoryProgramRepository(): ProgramRepository
{
    return new class implements ProgramRepository
    {
        /** @var array<string, EducationalProgram> */
        private array $programs = [];

        public function save(EducationalProgram $program): void
        {
            $this->programs[$program->code()->value()] = $program;
        }

        public function findById(ProgramId $id): ?EducationalProgram
        {
            foreach ($this->programs as $program) {
                if ($program->id()->equals($id)) {
                    return $program;
                }
            }

            return null;
        }

        public function findByCode(ProgramCode $code): ?EducationalProgram
        {
            return $this->programs[$code->value()] ?? null;
        }

        public function existsByCode(ProgramCode $code): bool
        {
            return isset($this->programs[$code->value()]);
        }

        public function all(): array
        {
            return array_values($this->programs);
        }
    };
}

it('crea un programa normalizado y devuelve su representacion publica', function (): void {
    $handler = new CreateProgramHandler(inMemoryProgramRepository());

    $response = $handler->handle(new CreateProgramCommand(
        code: ' moto-learner-01 ',
        title: 'Programa inicial de motocicleta',
        description: 'Formacion regional para aprendices.',
        minAge: 16,
        maxAge: 18,
        licenseStages: ['unlicensed', 'learner'],
        contexts: ['general', 'institutional'],
        vehicleTypes: ['motorcycle'],
    ));

    $payload = $response->toArray();

    expect(Str::isUuid($payload['id']))->toBeTrue()
        ->and($payload)->toBe([
            'id' => $payload['id'],
            'code' => 'MOTO-LEARNER-01',
            'title' => 'Programa inicial de motocicleta',
            'description' => 'Formacion regional para aprendices.',
            'status' => 'draft',
            'audience' => [
                'min_age' => 16,
                'max_age' => 18,
                'license_stages' => ['unlicensed', 'learner'],
                'contexts' => ['general', 'institutional'],
                'vehicle_types' => ['motorcycle'],
            ],
            'courses' => [],
            'published_at' => null,
            'archived_at' => null,
        ]);
});

it('rechaza un codigo de programa existente', function (): void {
    $handler = new CreateProgramHandler(inMemoryProgramRepository());
    $command = new CreateProgramCommand(
        code: ' moto-learner-01 ',
        title: 'Programa inicial de motocicleta',
        description: 'Formacion regional para aprendices.',
        minAge: 16,
        maxAge: 18,
        licenseStages: ['learner'],
        contexts: ['general'],
        vehicleTypes: ['motorcycle'],
    );

    $handler->handle($command);
    $handler->handle($command);
})->throws(ProgramCodeAlreadyExists::class, 'Ya existe un programa con el código MOTO-LEARNER-01.');

it('lista los programas en el orden provisto por el repositorio', function (): void {
    $repository = inMemoryProgramRepository();
    $createHandler = new CreateProgramHandler($repository);

    $createHandler->handle(new CreateProgramCommand(
        code: 'MOTO-LEARNER-01',
        title: 'Programa inicial de motocicleta',
        description: 'Formacion regional para aprendices.',
        minAge: 16,
        maxAge: 18,
        licenseStages: ['learner'],
        contexts: ['general'],
        vehicleTypes: ['motorcycle'],
    ));
    $createHandler->handle(new CreateProgramCommand(
        code: 'AUTO-PRO-01',
        title: 'Programa profesional de automovil',
        description: 'Formacion regional para conductores profesionales.',
        minAge: 21,
        maxAge: null,
        licenseStages: ['professional'],
        contexts: ['corporate'],
        vehicleTypes: ['automobile'],
    ));

    $responses = (new ListProgramsHandler($repository))->handle(new ListProgramsQuery);

    expect(array_map(static fn ($response): string => $response->toArray()['code'], $responses))
        ->toBe(['MOTO-LEARNER-01', 'AUTO-PRO-01']);
});
