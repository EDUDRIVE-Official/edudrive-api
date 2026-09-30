<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\ViewModels;

/** A display of digital practice only. This is not a competency or trust assessment. */
final class DescubroPracticeSummary
{
    /**
     * Uses the saved sequence rather than the bounded attempt log, so an old
     * completed practice cannot disappear when the learner practices again.
     *
     * @param  array<string, mixed>  $run
     * @param  list<array<string, mixed>>  $questions
     * @return array<string, mixed>
     */
    public static function fromRun(array $run, array $questions): array
    {
        $completed = (bool) $run['completed_once'];
        $phase = $run['phase'];
        $current = (int) $run['question'];
        $skills = [];
        foreach ([
            'S1' => 'Reconocer la acera y la calzada',
            'S2' => 'Detenerse y comprobar con mi acompañante',
            'S3' => 'Cruzar junto a la persona adulta',
        ] as $code => $title) {
            $total = 0;
            $practiced = 0;
            $started = false;
            foreach ($questions as $index => $question) {
                if ($question['skill'] !== $code) {
                    continue;
                }
                $total++;
                if ($completed || ($phase === 'practice' && ($index < $current || ($index === $current && $run['feedback'] === 'success')))) {
                    $practiced++;
                    $started = true;
                } elseif ($phase === 'practice' && $index === $current && $run['feedback'] === 'retry') {
                    $started = true;
                }
            }
            $status = $total > 0 && $practiced === $total ? 'practiced' : ($started ? 'in_progress' : 'pending');
            $skills[] = [
                'code' => 'EDU-PED-001.E1.'.$code,
                'title' => $title,
                'status' => $status,
                'label' => ['practiced' => 'Practicado en pantalla', 'in_progress' => 'En práctica', 'pending' => 'Por practicar'][$status],
            ];
        }

        return [
            'skills' => $skills,
            'phase' => match ($phase) {
                'learn' => 'Aprendo · Paso '.($run['lesson'] + 1).' de 3',
                'practice' => 'Practico · Decisión '.($current + 1).' de '.count($questions),
                'result' => 'Práctica digital completada',
                default => 'Por empezar',
            },
            'action' => match ($phase) {
                'home' => 'Empezar juntos',
                'result' => 'Ver mi práctica',
                default => 'Continuar donde quedé',
            },
            'next' => match ($phase) {
                'home' => 'Empezar la explicación con tu acompañante.',
                'learn' => 'Seguir la explicación, un paso a la vez.',
                'practice' => $questions[$current]['title'],
                default => 'Revisar con tu acompañante lo que practicaste.',
            },
            'repeating' => $completed && $phase === 'practice',
        ];
    }
}
