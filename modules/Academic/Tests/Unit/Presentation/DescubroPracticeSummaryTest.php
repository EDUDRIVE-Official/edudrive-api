<?php

declare(strict_types=1);

use Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController as Crossing;

it('builds a practice summary without treating a partial decision as a practiced skill', function (array $changes, array $expected): void {
    $run = array_replace(Crossing::initial(), $changes);
    $summary = Crossing::summary($run);
    expect(array_column($summary['skills'], 'status'))->toBe($expected)
        ->and(array_column($summary['skills'], 'code'))->toBe(['EDU-PED-001.E1.S1', 'EDU-PED-001.E1.S2', 'EDU-PED-001.E1.S3']);
})->with([
    'before starting' => [[], ['pending', 'pending', 'pending']],
    'explanation is not practice' => [['phase' => 'learn', 'lesson' => 2], ['pending', 'pending', 'pending']],
    'retry needs more practice' => [['phase' => 'practice', 'feedback' => 'retry'], ['in_progress', 'pending', 'pending']],
    'first decision practiced' => [['phase' => 'practice', 'feedback' => 'success'], ['practiced', 'pending', 'pending']],
    'waiting is only half of S2' => [['phase' => 'practice', 'question' => 1, 'feedback' => 'success'], ['practiced', 'in_progress', 'pending']],
    'both S2 decisions practiced' => [['phase' => 'practice', 'question' => 2, 'feedback' => 'success'], ['practiced', 'practiced', 'pending']],
    'all digital decisions practiced' => [['phase' => 'practice', 'question' => 3, 'feedback' => 'success'], ['practiced', 'practiced', 'practiced']],
    'repeat preserves completed round despite bounded attempts' => [['phase' => 'practice', 'completed_once' => true, 'feedback' => 'retry', 'attempts' => []], ['practiced', 'practiced', 'practiced']],
]);

it('explains in the summary when a completed practice is being repeated', function (): void {
    $run = array_replace(Crossing::initial(), ['phase' => 'practice', 'question' => 1, 'completed_once' => true]);
    $summary = Crossing::summary($run);
    expect($summary['repeating'])->toBeTrue()
        ->and($summary['phase'])->toBe('Practico · Decisión 2 de 4')
        ->and($summary['next'])->toBe('Viene un bus. ¿Qué hacemos?')
        ->and($summary['action'])->toBe('Continuar donde quedé');
});
