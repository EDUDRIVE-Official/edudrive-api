<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Enums\EvidenceType;
use Modules\RoadPassport\Domain\Enums\RoadPassportHistoryType;
use Modules\RoadPassport\Domain\Enums\RoadPassportStatus;
use Modules\RoadPassport\Domain\Exceptions\InvalidRoadPassportLevel;
use Modules\RoadPassport\Domain\Exceptions\InvalidRoadPassportTransition;
use Modules\RoadPassport\Domain\ValueObjects\Evidence;
use Modules\RoadPassport\Domain\ValueObjects\PassportHistoryEntry;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

function newRoadPassport(): RoadPassport
{
    return RoadPassport::create(
        id: RoadPassportId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        issuedAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('se crea activo, en nivel 1, sin historial y sin evidencia', function (): void {
    $passport = newRoadPassport();

    expect($passport->status())->toBe(RoadPassportStatus::Active)
        ->and($passport->level())->toBe(1)
        ->and($passport->history())->toBe([])
        ->and($passport->evidence())->toBe([]);
});

it('suspende un pasaporte activo y registra el cambio en el historial', function (): void {
    $passport = newRoadPassport();

    $passport->suspend('Documentación pendiente', new DateTimeImmutable('2026-08-27T00:00:00+00:00'));

    expect($passport->status())->toBe(RoadPassportStatus::Suspended)
        ->and($passport->history())->toHaveCount(1);

    $entry = $passport->history()[0];
    expect($entry->type)->toBe(RoadPassportHistoryType::StatusChanged)
        ->and($entry->fromValue)->toBe('active')
        ->and($entry->toValue)->toBe('suspended')
        ->and($entry->reason)->toBe('Documentación pendiente');
});

it('rechaza suspender un pasaporte que no esta activo', function (): void {
    $passport = newRoadPassport();
    $passport->suspend(null, new DateTimeImmutable('now'));

    expect(fn () => $passport->suspend(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidRoadPassportTransition::class);
});

it('reactiva un pasaporte suspendido', function (): void {
    $passport = newRoadPassport();
    $passport->suspend(null, new DateTimeImmutable('now'));

    $passport->reactivate(new DateTimeImmutable('now'));

    expect($passport->status())->toBe(RoadPassportStatus::Active)
        ->and($passport->history())->toHaveCount(2);
});

it('rechaza reactivar un pasaporte que no esta suspendido', function (): void {
    $passport = newRoadPassport();

    expect(fn () => $passport->reactivate(new DateTimeImmutable('now')))
        ->toThrow(InvalidRoadPassportTransition::class);
});

it('revoca un pasaporte activo o suspendido de forma terminal', function (): void {
    $active = newRoadPassport();
    $active->revoke('Fraude detectado', new DateTimeImmutable('now'));
    expect($active->status())->toBe(RoadPassportStatus::Revoked);

    $suspended = newRoadPassport();
    $suspended->suspend(null, new DateTimeImmutable('now'));
    $suspended->revoke(null, new DateTimeImmutable('now'));
    expect($suspended->status())->toBe(RoadPassportStatus::Revoked);
});

it('rechaza revocar un pasaporte ya revocado', function (): void {
    $passport = newRoadPassport();
    $passport->revoke(null, new DateTimeImmutable('now'));

    expect(fn () => $passport->revoke(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidRoadPassportTransition::class);
});

it('rechaza cualquier transicion sobre un pasaporte revocado', function (): void {
    $passport = newRoadPassport();
    $passport->revoke(null, new DateTimeImmutable('now'));

    expect(fn () => $passport->suspend(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidRoadPassportTransition::class)
        ->and(fn () => $passport->reactivate(new DateTimeImmutable('now')))
        ->toThrow(InvalidRoadPassportTransition::class)
        ->and(fn () => $passport->changeLevel(2, new DateTimeImmutable('now')))
        ->toThrow(InvalidRoadPassportLevel::class);
});

it('sube de nivel mientras esta activo y registra el cambio en el historial', function (): void {
    $passport = newRoadPassport();

    $passport->changeLevel(3, new DateTimeImmutable('now'));

    expect($passport->level())->toBe(3)
        ->and($passport->history())->toHaveCount(1);

    $entry = $passport->history()[0];
    expect($entry->type)->toBe(RoadPassportHistoryType::LevelChanged)
        ->and($entry->fromValue)->toBe('1')
        ->and($entry->toValue)->toBe('3');
});

it('rechaza bajar o mantener el nivel', function (): void {
    $passport = newRoadPassport();
    $passport->changeLevel(2, new DateTimeImmutable('now'));

    expect(fn () => $passport->changeLevel(2, new DateTimeImmutable('now')))
        ->toThrow(InvalidRoadPassportLevel::class)
        ->and(fn () => $passport->changeLevel(1, new DateTimeImmutable('now')))
        ->toThrow(InvalidRoadPassportLevel::class);
});

it('rechaza cambiar de nivel mientras esta suspendido', function (): void {
    $passport = newRoadPassport();
    $passport->suspend(null, new DateTimeImmutable('now'));

    expect(fn () => $passport->changeLevel(2, new DateTimeImmutable('now')))
        ->toThrow(InvalidRoadPassportLevel::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = RoadPassportId::fromString((string) Str::uuid());
    $userId = (string) Str::uuid();
    $issuedAt = new DateTimeImmutable('2026-08-26T10:00:00+00:00');
    $historyEntry = PassportHistoryEntry::statusChanged(
        RoadPassportStatus::Active,
        RoadPassportStatus::Suspended,
        new DateTimeImmutable('2026-08-27T00:00:00+00:00'),
        'Motivo',
    );

    $evidence = Evidence::create(EvidenceType::CourseCompleted, 'enrollment-1', 'course-1', new DateTimeImmutable('now'), []);

    $passport = RoadPassport::restore(
        id: $id,
        userId: $userId,
        status: RoadPassportStatus::Suspended,
        level: 2,
        issuedAt: $issuedAt,
        history: [$historyEntry],
        evidence: [$evidence],
    );

    expect($passport->id()->equals($id))->toBeTrue()
        ->and($passport->userId())->toBe($userId)
        ->and($passport->status())->toBe(RoadPassportStatus::Suspended)
        ->and($passport->level())->toBe(2)
        ->and($passport->issuedAt())->toBe($issuedAt)
        ->and($passport->history())->toBe([$historyEntry])
        ->and($passport->evidence())->toBe([$evidence]);
});

it('registra evidencia nueva y la conserva en orden', function (): void {
    $passport = newRoadPassport();
    $courseCompleted = Evidence::create(EvidenceType::CourseCompleted, 'enrollment-1', 'course-1', new DateTimeImmutable('now'), []);
    $examPassed = Evidence::create(EvidenceType::ExamPassed, 'attempt-1', 'course-1', new DateTimeImmutable('now'), ['percentage' => 80]);

    $passport->recordEvidence($courseCompleted);
    $passport->recordEvidence($examPassed);

    expect($passport->evidence())->toBe([$courseCompleted, $examPassed]);
});

it('ignora evidencia duplicada por tipo y sujeto', function (): void {
    $passport = newRoadPassport();
    $first = Evidence::create(EvidenceType::CourseCompleted, 'enrollment-1', 'course-1', new DateTimeImmutable('now'), []);
    $duplicate = Evidence::create(EvidenceType::CourseCompleted, 'enrollment-1', 'course-1', new DateTimeImmutable('now'), []);

    $passport->recordEvidence($first);
    $passport->recordEvidence($duplicate);

    expect($passport->evidence())->toHaveCount(1);
});

it('registra evidencia sin importar el estado del pasaporte', function (): void {
    $passport = newRoadPassport();
    $passport->suspend(null, new DateTimeImmutable('now'));

    $passport->recordEvidence(Evidence::create(EvidenceType::ExamPassed, 'attempt-1', 'course-1', new DateTimeImmutable('now'), []));

    expect($passport->evidence())->toHaveCount(1);
});
