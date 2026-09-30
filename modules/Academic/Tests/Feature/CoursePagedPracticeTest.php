<?php

declare(strict_types=1);

it('connects all ten model practices to original course answer fields', function (): void {
    $draft = json_decode(file_get_contents(resource_path('curriculum/editorial/camino-pasajero-v1.json')), true, 512, JSON_THROW_ON_ERROR);
    foreach ($draft['lessons'] as $number => $lesson) {
        foreach ([1, 2] as $position) {
            $id = 'original-'.$number.'-'.$position;
            $block = ['id' => $id, 'payload' => $lesson['blocks'][$position]['payload']];
            $html = view('courses.blocks.scenario', ['block' => $block, 'answerField' => true, 'uniformEditorialFeedback' => true, 'courseLessonPage' => $position])->render();
            expect($html)->toContain('name="scenario_answers['.$id.']"')
                ->toContain('✓ Respuesta correcta')->toContain('⚠ Respuesta incorrecta');
            expect(substr_count($html, 'name="scenario_answers['.$id.']"'))->toBe(1);
            if ($number >= 3) {
                expect($html)->toContain('passengerPreview3d');
            }
            $preview = view('courses.blocks.scenario', ['block' => $block, 'answerField' => false, 'uniformEditorialFeedback' => true])->render();
            expect($preview)->not->toContain('name="scenario_answers['.$id.']"');
        }
    }
});
