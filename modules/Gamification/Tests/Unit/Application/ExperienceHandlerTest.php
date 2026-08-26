<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Application\Commands\RecordExperienceCommand;
use Modules\Gamification\Application\Queries\GetMyExperienceSummaryQuery;
use Modules\Gamification\Application\Responses\ExperienceEntryResponse;
use Modules\Gamification\Application\Responses\ExperienceSummaryResponse;
use Modules\Gamification\Application\UseCases\GetMyExperienceSummaryHandler;
use Modules\Gamification\Application\UseCases\RecordExperienceHandler;
use Modules\Gamification\Domain\Entities\ExperienceEntry;
use Modules\Gamification\Domain\Repositories\ExperienceEntryRepository;

final class InMemoryExperienceEntryRepository implements ExperienceEntryRepository
{
    /** @var list<ExperienceEntry> */
    public array $items = [];

    public function save(ExperienceEntry $entry): void
    {
        $this->items[] = $entry;
    }

    /** @return list<ExperienceEntry> */
    public function allForUser(string $userId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (ExperienceEntry $item): bool => $item->userId() === $userId,
        ));
    }
}

it('registra puntos de experiencia para un usuario', function (): void {
    $experienceEntries = new InMemoryExperienceEntryRepository;
    $userId = (string) Str::uuid();

    $response = (new RecordExperienceHandler($experienceEntries))->handle(new RecordExperienceCommand(
        userId: $userId,
        points: 50,
        competencyId: 'manejo-defensivo',
        reason: 'Completó la sesión práctica sin infracciones.',
    ));

    expect($response)->toBeInstanceOf(ExperienceEntryResponse::class)
        ->and($response->userId)->toBe($userId)
        ->and($response->points)->toBe(50)
        ->and($response->competencyId)->toBe('manejo-defensivo')
        ->and($experienceEntries->allForUser($userId))->toHaveCount(1);
});

it('rechaza registrar puntos no positivos', function (): void {
    $experienceEntries = new InMemoryExperienceEntryRepository;

    expect(fn () => (new RecordExperienceHandler($experienceEntries))->handle(new RecordExperienceCommand((string) Str::uuid(), 0, null, 'Motivo')))
        ->toThrow(InvalidArgumentException::class);
});

it('consulta el resumen de experiencia del usuario autenticado', function (): void {
    $experienceEntries = new InMemoryExperienceEntryRepository;
    $userId = (string) Str::uuid();
    (new RecordExperienceHandler($experienceEntries))->handle(new RecordExperienceCommand($userId, 60, 'manejo-defensivo', 'Motivo 1'));
    (new RecordExperienceHandler($experienceEntries))->handle(new RecordExperienceCommand($userId, 60, 'manejo-defensivo', 'Motivo 2'));
    (new RecordExperienceHandler($experienceEntries))->handle(new RecordExperienceCommand((string) Str::uuid(), 500, null, 'Puntos de otro usuario'));

    $response = (new GetMyExperienceSummaryHandler($experienceEntries))->handle(new GetMyExperienceSummaryQuery($userId));

    expect($response)->toBeInstanceOf(ExperienceSummaryResponse::class)
        ->and($response->totalPoints)->toBe(120)
        ->and($response->generalLevel)->toBe(2)
        ->and($response->competencies)->toHaveCount(1);
});

it('devuelve nivel uno y sin competencias cuando el usuario no tiene experiencia registrada', function (): void {
    $experienceEntries = new InMemoryExperienceEntryRepository;

    $response = (new GetMyExperienceSummaryHandler($experienceEntries))->handle(new GetMyExperienceSummaryQuery((string) Str::uuid()));

    expect($response->totalPoints)->toBe(0)
        ->and($response->generalLevel)->toBe(1)
        ->and($response->competencies)->toBe([]);
});
