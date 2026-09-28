<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\ContentBlocks\TextContentBlock;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Domain\ValueObjects\LessonId;

final readonly class PilotDraftWorkspace
{
    public const CODE = 'P912-PILOT-01';

    public function __construct(
        private CourseRepository $courses,
        private UnitContentRepository $contents,
    ) {}

    /** @return array{course_id: string, created: bool} */
    public function ensure(): array
    {
        $code = CourseCode::fromString(self::CODE);
        $existing = $this->courses->findByCode($code);
        if ($existing !== null) {
            abort_unless($existing->status() === CourseStatus::Draft, 409, 'El código del espacio piloto ya pertenece a un curso que no está en borrador.');

            return ['course_id' => $existing->id()->value(), 'created' => false];
        }

        return DB::transaction(function () use ($code): array {
            $courseId = CourseId::fromString((string) Str::uuid());
            $unitId = CourseUnitId::fromString((string) Str::uuid());
            $course = Course::create(
                $courseId,
                $code,
                CourseTitle::fromString('Piloto Primaria 9–12: Peatones y pasajeros'),
                'Espacio de trabajo interno para integrar y revisar escenas antes de cualquier publicación.',
                'Practicar decisiones seguras de visibilidad, cruce, cambio de plan y descenso protegido.',
                'Uso interno con acompañamiento adulto; no habilitado para aplicación real con estudiantes.',
                CourseModality::Virtual,
                2,
            );
            $course->replaceCurriculum([
                CourseModule::create(
                    CourseModuleId::fromString((string) Str::uuid()),
                    CurriculumCode::fromString('MOD-P912'),
                    'Decisiones seguras como peatón y pasajero',
                    'Módulo interno para preparar, revisar e integrar las cuatro escenas del piloto.',
                    'Distinguir información suficiente, trayectorias de riesgo, rutas bloqueadas y lugares de descenso.',
                    80,
                    1,
                    [],
                    [CourseUnit::create(
                        $unitId,
                        CurriculumCode::fromString('U01-DECISIONES'),
                        'Observar, comprobar, cambiar el plan y comunicar',
                        'Unidad borrador alineada con el recorrido visual P912.',
                        'Elegir acciones protegidas sin inferir seguridad únicamente de una señal o de la ausencia de vehículos visibles.',
                        80,
                        1,
                        [],
                    )],
                ),
            ]);
            $this->courses->save($course);
            $lessons = [];
            foreach ($this->lessonDefinitions() as $index => $definition) {
                $block = TextContentBlock::fromPayload(
                    ContentBlockId::fromString((string) Str::uuid()),
                    1,
                    ['markdown' => $definition['markdown'], 'title' => 'Estado editorial'],
                );
                $lessons[] = Lesson::create(
                    LessonId::fromString((string) Str::uuid()),
                    CurriculumCode::fromString($definition['code']),
                    $definition['title'],
                    $definition['summary'],
                    20,
                    $index + 1,
                    [$block],
                );
            }
            $this->contents->replaceAtomically($courseId, $unitId, UnitContent::create($unitId, $lessons));

            return ['course_id' => $courseId->value(), 'created' => true];
        });
    }

    /** @return list<array{code: string, title: string, summary: string, markdown: string}> */
    private function lessonDefinitions(): array
    {
        return [
            ['code' => 'L01-VISIBILIDAD', 'title' => 'Observar antes de avanzar', 'summary' => 'Visibilidad obstruida por una van.', 'markdown' => 'Esta lección es un espacio borrador. La escena **La van que bloquea la vista** solo podrá integrarse después de completar su revisión.'],
            ['code' => 'L02-TRAYECTORIAS', 'title' => 'Comprobar señales y trayectorias', 'summary' => 'Conflicto entre señal favorable y vehículo que gira.', 'markdown' => 'Esta lección es un espacio borrador. La escena **El carro que gira** solo podrá integrarse después de completar su revisión.'],
            ['code' => 'L03-CAMBIO-PLAN', 'title' => 'Cambiar el plan ante una ruta bloqueada', 'summary' => 'Acera cerrada sin alternativa protegida conocida.', 'markdown' => 'Esta lección es un espacio borrador. La escena **Ruta interrumpida** solo podrá integrarse después de completar su revisión.'],
            ['code' => 'L04-DESCENSO', 'title' => 'Comunicar un descenso inseguro', 'summary' => 'La puerta queda frente a un espacio sin acera.', 'markdown' => 'Esta lección es un espacio borrador. La escena **Cambió el lugar de descenso** solo podrá integrarse después de completar su revisión.'],
        ];
    }
}
