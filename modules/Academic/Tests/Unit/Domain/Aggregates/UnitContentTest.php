<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\ContentBlocks\ContentBlock;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Exceptions\InvalidBlockPosition;
use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\Exceptions\InvalidLessonPosition;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Domain\ValueObjects\LessonId;

function lessonId(string $value = '01981a64-8300-7b1d-b442-764ea7f915d0'): LessonId
{
    return LessonId::fromString($value);
}

function unitContentId(): CourseUnitId
{
    return CourseUnitId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0');
}

function lessonBlock(
    string $id = '01981a64-8300-7b1d-b442-764ea7f915e0',
    int $position = 1,
): ContentBlock {
    return ContentBlockFactory::create(
        ContentBlockId::fromString($id),
        'text',
        $position,
        ['markdown' => 'Contenido de la leccion.'],
    );
}

/** @param list<ContentBlock>|null $blocks */
function unitLesson(
    string $id = '01981a64-8300-7b1d-b442-764ea7f915d0',
    string $code = 'LEC-01',
    int $position = 1,
    ?array $blocks = null,
): Lesson {
    return Lesson::create(
        id: LessonId::fromString($id),
        code: CurriculumCode::fromString($code),
        title: 'Leccion introductoria',
        summary: null,
        durationMinutes: null,
        position: $position,
        blocks: $blocks ?? [lessonBlock()],
    );
}

it('normaliza identificadores de leccion uuid y los compara por valor', function (): void {
    $id = LessonId::fromString(' 01981A64-8300-7B1D-B442-764EA7F915D0 ');

    expect($id->value())->toBe('01981a64-8300-7b1d-b442-764ea7f915d0')
        ->and((string) $id)->toBe('01981a64-8300-7b1d-b442-764ea7f915d0')
        ->and($id->equals(lessonId()))->toBeTrue();
});

it('rechaza identificadores de leccion que no son uuid', function (): void {
    LessonId::fromString('lesson-1');
})->throws(InvalidArgumentException::class);

it('representa una unidad sin lecciones como contenido incompleto', function (): void {
    $content = UnitContent::create(unitContentId(), []);

    expect($content->unitId()->equals(unitContentId()))->toBeTrue()
        ->and($content->lessons())->toBe([])
        ->and($content->isComplete())->toBeFalse();
});

it('crea una leccion normalizada con bloques ordenados', function (): void {
    $block = lessonBlock();
    $lesson = Lesson::create(
        id: lessonId(),
        code: CurriculumCode::fromString(' lec-01 '),
        title: '  Conduccion preventiva  ',
        summary: '  Riesgos y anticipacion.  ',
        durationMinutes: 25,
        position: 1,
        blocks: [$block],
    );

    expect($lesson->id()->equals(lessonId()))->toBeTrue()
        ->and($lesson->code()->value())->toBe('LEC-01')
        ->and($lesson->title())->toBe('Conduccion preventiva')
        ->and($lesson->summary())->toBe('Riesgos y anticipacion.')
        ->and($lesson->durationMinutes())->toBe(25)
        ->and($lesson->position())->toBe(1)
        ->and($lesson->blocks())->toBe([$block]);
});

it('convierte el resumen vacio en null y admite duracion ausente', function (): void {
    $lesson = Lesson::create(
        id: lessonId(),
        code: CurriculumCode::fromString('LEC-01'),
        title: 'Introduccion',
        summary: '  ',
        durationMinutes: null,
        position: 1,
        blocks: [lessonBlock()],
    );

    expect($lesson->summary())->toBeNull()
        ->and($lesson->durationMinutes())->toBeNull();
});

it('rechaza metadatos invalidos de leccion', function (string $title, ?string $summary, ?int $duration): void {
    Lesson::create(
        id: lessonId(),
        code: CurriculumCode::fromString('LEC-01'),
        title: $title,
        summary: $summary,
        durationMinutes: $duration,
        position: 1,
        blocks: [lessonBlock()],
    );
})->with([
    'titulo vacio' => [' ', null, null],
    'titulo extenso' => [str_repeat('A', 181), null, null],
    'resumen extenso' => ['Titulo', str_repeat('A', 5001), null],
    'duracion cero' => ['Titulo', null, 0],
    'duracion negativa' => ['Titulo', null, -1],
])->throws(InvalidContentBlock::class);

it('requiere al menos un bloque en cada leccion', function (): void {
    unitLesson(blocks: []);
})->throws(InvalidContentBlock::class);

it('rechaza posiciones no positivas de leccion con su contrato publico', function (): void {
    try {
        unitLesson(position: 0);
    } catch (InvalidLessonPosition $exception) {
        expect($exception->errorCode())->toBe('INVALID_LESSON_POSITION')
            ->and($exception->statusCode())->toBe(422);

        return;
    }

    $this->fail('Se esperaba InvalidLessonPosition.');
});

it('exige posiciones consecutivas de lecciones desde uno', function (array $lessons): void {
    UnitContent::create(unitContentId(), $lessons);
})->with([
    'hueco' => [fn (): array => [
        unitLesson(position: 1),
        unitLesson('01981a64-8300-7b1d-b442-764ea7f915d1', 'LEC-02', 3, [
            lessonBlock('01981a64-8300-7b1d-b442-764ea7f915e1'),
        ]),
    ]],
    'repetida' => [fn (): array => [
        unitLesson(position: 1),
        unitLesson('01981a64-8300-7b1d-b442-764ea7f915d1', 'LEC-02', 1, [
            lessonBlock('01981a64-8300-7b1d-b442-764ea7f915e1'),
        ]),
    ]],
])->throws(InvalidLessonPosition::class);

it('rechaza posiciones no consecutivas de bloques con su contrato publico', function (array $blocks): void {
    try {
        unitLesson(blocks: $blocks);
    } catch (InvalidBlockPosition $exception) {
        expect($exception->errorCode())->toBe('INVALID_BLOCK_POSITION')
            ->and($exception->statusCode())->toBe(422);

        return;
    }

    $this->fail('Se esperaba InvalidBlockPosition.');
})->with([
    'hueco' => [fn (): array => [lessonBlock(position: 2)]],
    'repetida' => [fn (): array => [
        lessonBlock(),
        lessonBlock('01981a64-8300-7b1d-b442-764ea7f915e1'),
    ]],
]);

it('rechaza identificadores de leccion repetidos en una unidad', function (): void {
    UnitContent::create(unitContentId(), [
        unitLesson(),
        unitLesson(code: 'LEC-02', position: 2, blocks: [
            lessonBlock('01981a64-8300-7b1d-b442-764ea7f915e1'),
        ]),
    ]);
})->throws(InvalidContentBlock::class);

it('rechaza identificadores de bloque repetidos entre lecciones', function (): void {
    UnitContent::create(unitContentId(), [
        unitLesson(),
        unitLesson('01981a64-8300-7b1d-b442-764ea7f915d1', 'LEC-02', 2),
    ]);
})->throws(InvalidContentBlock::class);

it('rechaza codigos de leccion repetidos sin distinguir casing', function (): void {
    UnitContent::create(unitContentId(), [
        unitLesson(code: 'lec-01'),
        unitLesson('01981a64-8300-7b1d-b442-764ea7f915d1', 'LEC-01', 2, [
            lessonBlock('01981a64-8300-7b1d-b442-764ea7f915e1'),
        ]),
    ]);
})->throws(InvalidContentBlock::class);

it('no muta el contenido previo cuando el reemplazo candidato es invalido', function (): void {
    $originalLesson = unitLesson();
    $content = UnitContent::create(unitContentId(), [$originalLesson]);

    try {
        $content->replaceLessons([
            unitLesson(),
            unitLesson('01981a64-8300-7b1d-b442-764ea7f915d1', 'LEC-02', 3, [
                lessonBlock('01981a64-8300-7b1d-b442-764ea7f915e1'),
            ]),
        ]);
    } catch (InvalidLessonPosition) {
        expect($content->lessons())->toBe([$originalLesson]);

        return;
    }

    $this->fail('Se esperaba InvalidLessonPosition.');
});

it('solo esta completo cuando tiene lecciones con bloques validos', function (): void {
    $content = UnitContent::create(unitContentId(), [unitLesson()]);

    expect($content->isComplete())->toBeTrue();
});
