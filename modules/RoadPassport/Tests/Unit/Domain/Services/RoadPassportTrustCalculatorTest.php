<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Enums\EvidenceType;
use Modules\RoadPassport\Domain\Services\RoadPassportTrustCalculator;
use Modules\RoadPassport\Domain\ValueObjects\Evidence;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

function newTrustPassport(): RoadPassport
{
    return RoadPassport::create(
        id: RoadPassportId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
    );
}

it('devuelve cero sin evidencia', function (): void {
    $passport = newTrustPassport();
    $now = new DateTimeImmutable('2026-08-26T00:00:00+00:00');

    expect((new RoadPassportTrustCalculator)->calculate($passport, $now))->toBe(0);
});

it('pesa mas un examen aprobado que un curso completado, ambos recientes', function (): void {
    $now = new DateTimeImmutable('2026-08-26T00:00:00+00:00');

    $examPassport = newTrustPassport();
    $examPassport->recordEvidence(Evidence::create(EvidenceType::ExamPassed, 'attempt-1', 'course-1', $now, []));

    $coursePassport = newTrustPassport();
    $coursePassport->recordEvidence(Evidence::create(EvidenceType::CourseCompleted, 'enrollment-1', 'course-1', $now, []));

    $calculator = new RoadPassportTrustCalculator;

    expect($calculator->calculate($examPassport, $now))->toBeGreaterThan($calculator->calculate($coursePassport, $now));
});

it('no degrada evidencia de hasta 90 dias de antiguedad', function (): void {
    $now = new DateTimeImmutable('2026-08-26T00:00:00+00:00');
    $passport = newTrustPassport();
    $passport->recordEvidence(Evidence::create(EvidenceType::ExamPassed, 'attempt-1', 'course-1', $now->modify('-90 days'), []));

    // peso base 15 * factor 1.0 * multiplicador de consistencia (1 pieza) 0.6 = 9
    expect((new RoadPassportTrustCalculator)->calculate($passport, $now))->toBe(9);
});

it('degrada linealmente evidencia entre 90 y 365 dias', function (): void {
    $now = new DateTimeImmutable('2026-08-26T00:00:00+00:00');
    $passport = newTrustPassport();
    $passport->recordEvidence(Evidence::create(EvidenceType::ExamPassed, 'attempt-1', 'course-1', $now->modify('-227 days'), []));

    // ~punto medio entre 90 y 365 -> factor ~0.6; 15 * 0.6 * 0.6 (multiplicador) ~ 5.4 -> redondea a 5
    expect((new RoadPassportTrustCalculator)->calculate($passport, $now))->toBe(5);
});

it('mantiene un piso minimo de peso para evidencia de un año o mas', function (): void {
    $now = new DateTimeImmutable('2026-08-26T00:00:00+00:00');
    $passport = newTrustPassport();
    $passport->recordEvidence(Evidence::create(EvidenceType::ExamPassed, 'attempt-1', 'course-1', $now->modify('-500 days'), []));

    // 15 * 0.2 (piso) * 0.6 (multiplicador) = 1.8 -> redondea a 2, nunca cero
    expect((new RoadPassportTrustCalculator)->calculate($passport, $now))->toBe(2);
});

it('aumenta la confianza con mas evidencia independiente, con retornos decrecientes', function (): void {
    $now = new DateTimeImmutable('2026-08-26T00:00:00+00:00');
    $onePiece = newTrustPassport();
    $onePiece->recordEvidence(Evidence::create(EvidenceType::ExamPassed, 'attempt-1', 'course-1', $now, []));

    $fivePieces = newTrustPassport();
    foreach (range(1, 5) as $i) {
        $fivePieces->recordEvidence(Evidence::create(EvidenceType::ExamPassed, "attempt-{$i}", 'course-1', $now, []));
    }

    $calculator = new RoadPassportTrustCalculator;
    $oneScore = $calculator->calculate($onePiece, $now);
    $fiveScore = $calculator->calculate($fivePieces, $now);

    // una pieza: 15 * 1.0 * 0.6 = 9. cinco piezas: (15*5) * min(1.0, 0.5+0.5) = 75 * 1.0 = 75.
    expect($oneScore)->toBe(9)
        ->and($fiveScore)->toBe(75)
        ->and($fiveScore)->toBeGreaterThan($oneScore * 5);
});

it('acota el resultado a 100', function (): void {
    $now = new DateTimeImmutable('2026-08-26T00:00:00+00:00');
    $passport = newTrustPassport();
    foreach (range(1, 10) as $i) {
        $passport->recordEvidence(Evidence::create(EvidenceType::ExamPassed, "attempt-{$i}", 'course-1', $now, []));
    }

    expect((new RoadPassportTrustCalculator)->calculate($passport, $now))->toBe(100);
});
