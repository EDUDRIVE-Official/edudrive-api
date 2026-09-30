<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Support\DemoCoursePublication;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\AddCompetencyIndicatorCommand;
use Modules\Academic\Application\Commands\AddSubcompetencyCommand;
use Modules\Academic\Application\Commands\ApproveCourseCommand;
use Modules\Academic\Application\Commands\CreateCompetencyCommand;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Commands\ReopenCourseCommand;
use Modules\Academic\Application\Commands\ReplaceCourseCurriculumCommand;
use Modules\Academic\Application\Commands\ReplaceUnitContentCommand;
use Modules\Academic\Application\Commands\SubmitCourseForReviewCommand;
use Modules\Academic\Application\DTO\ContentBlockInput;
use Modules\Academic\Application\DTO\CourseModuleInput;
use Modules\Academic\Application\DTO\CourseUnitInput;
use Modules\Academic\Application\DTO\LessonInput;
use Modules\Academic\Application\UseCases\AddCompetencyIndicatorHandler;
use Modules\Academic\Application\UseCases\AddSubcompetencyHandler;
use Modules\Academic\Application\UseCases\ApproveCourseHandler;
use Modules\Academic\Application\UseCases\CreateCompetencyHandler;
use Modules\Academic\Application\UseCases\CreateCourseHandler;
use Modules\Academic\Application\UseCases\PublishCourseHandler;
use Modules\Academic\Application\UseCases\ReopenCourseHandler;
use Modules\Academic\Application\UseCases\ReplaceCourseCurriculumHandler;
use Modules\Academic\Application\UseCases\ReplaceUnitContentHandler;
use Modules\Academic\Application\UseCases\SubmitCourseForReviewHandler;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\LessonLearningDesign;
use Ramsey\Uuid\Uuid;

final class SafeCrossingPilotSeeder extends Seeder
{
    private const string COURSE_CODE = 'EDU-EXP-001';

    private const string COMPETENCY_CODE = 'PEATON-CRUCE-SEGURO';

    private const string RISK_COMPETENCY_CODE = 'PEATON-GESTION-RIESGO';

    private const string COEXISTENCE_COMPETENCY_CODE = 'CIUDADANIA-CONVIVENCIA-VIAL';

    private const string SELF_CARE_COMPETENCY_CODE = 'MOVILIDAD-ATENCION-AUTOCUIDADO';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $existingCourse = DB::table('academic_courses')->where('code', self::COURSE_CODE)->first(['id']);
        if ($existingCourse !== null) {
            $this->upgradeExistingCourse((string) $existingCourse->id, $this->competencyId(), $this->riskCompetencyId(), $this->coexistenceCompetencyId(), $this->selfCareCompetencyId());

            return;
        }

        $competencyId = $this->competencyId();
        $riskCompetencyId = $this->riskCompetencyId();
        $coexistenceCompetencyId = $this->coexistenceCompetencyId();
        $selfCareCompetencyId = $this->selfCareCompetencyId();
        $course = app(CreateCourseHandler::class)->handle(new CreateCourseCommand(
            code: self::COURSE_CODE,
            title: 'Misión Camino Seguro',
            description: 'Un recorrido integral para aprender a cruzar, percibir riesgos, interpretar señales y regular la atención en situaciones cotidianas de movilidad.',
            objectives: 'Elegir y ejecutar cruces seguros; anticipar peligros; interpretar señales y prioridades; actuar con convivencia, atención y autocuidado.',
            prerequisites: 'No requiere conocimientos previos. Para niñas y niños se recomienda realizar la práctica acompañados por una persona adulta.',
            modality: 'virtual',
            durationHours: 7,
        ));

        $moduleId = (string) Str::uuid();
        $unitIds = [(string) Str::uuid(), (string) Str::uuid(), (string) Str::uuid()];
        $units = [
            new CourseUnitInput($unitIds[0], 'LUGAR-SEGURO', '1. Encuentra el lugar seguro', 'Distingue aceras, esquinas y pasos peatonales.', 'Elegir el punto de cruce con mejor visibilidad y señalización.', 38, 1, []),
            new CourseUnitInput($unitIds[1], 'MIRA-ESCUCHA', '2. Detente, mira y escucha', 'Practica una pausa consciente antes de entrar a la calzada.', 'Comprobar que los vehículos se han detenido y que es seguro cruzar.', 38, 2, [$unitIds[0]]),
            new CourseUnitInput($unitIds[2], 'CRUZA-CALMA', '3. Cruza con calma', 'Integra la secuencia completa en una decisión cotidiana.', 'Cruzar directamente, atento al entorno y sin correr ni distraerse.', 44, 3, [$unitIds[1]]),
        ];

        $riskModule = $this->riskModule($moduleId);
        app(ReplaceCourseCurriculumHandler::class)->handle(new ReplaceCourseCurriculumCommand($course->id, [
            new CourseModuleInput(
                $moduleId,
                'MISION-CRUCE',
                'Misión 1: Camino Seguro',
                'Tres retos para aprender una conducta que protege la vida en Costa Rica y en cualquier lugar del mundo.',
                'Completar la secuencia: elegir, detenerse, observar, escuchar y cruzar con calma.',
                120,
                1,
                [],
                $units,
            ),
            $riskModule,
            $coexistenceModule = $this->coexistenceModule($riskModule->id),
            $this->selfCareModule($coexistenceModule->id),
        ]));

        $lessons = $this->lessons($competencyId);
        $supplementalLessons = $this->supplementalLessons($competencyId);
        $advancedLessons = $this->advancedLessons($competencyId);
        foreach ($unitIds as $index => $unitId) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($course->id, $unitId, [$lessons[$index], $supplementalLessons[$index], $advancedLessons[$index]]));
        }
        $this->replaceRiskContent($course->id, $riskCompetencyId);
        $this->replaceCoexistenceContent($course->id, $coexistenceCompetencyId);
        $this->replaceSelfCareContent($course->id, $selfCareCompetencyId);
        $this->synchronizeCourseMetadata($course->id, $moduleId, $unitIds);

        if (! DemoCoursePublication::isReady($course->id)) {
            return;
        }

        app(SubmitCourseForReviewHandler::class)->handle(new SubmitCourseForReviewCommand($course->id));
        app(ApproveCourseHandler::class)->handle(new ApproveCourseCommand($course->id));
        app(PublishCourseHandler::class)->handle(new PublishCourseCommand($course->id));

    }

    private function upgradeExistingCourse(string $courseId, string $competencyId, string $riskCompetencyId, string $coexistenceCompetencyId, string $selfCareCompetencyId): void
    {
        $course = app(CourseRepository::class)->findById(CourseId::fromString($courseId));
        if ($course === null || (! $course->status()->isPublished() && ! $course->status()->isDraft()) || $course->modules() === [] || count($course->modules()[0]->units()) !== 3) {
            return;
        }

        $unitIds = array_map(static fn ($unit): string => $unit->id()->value(), $course->modules()[0]->units());
        /** @var array<string, string> $preservedIds */
        $preservedIds = DB::table('academic_lessons')
            ->whereIn('unit_id', $unitIds)
            ->whereIn('code', ['RETO-LUGAR', 'RETO-OBSERVA', 'RETO-INTEGRA'])
            ->pluck('id', 'code')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        if ($course->status()->isPublished()) {
            app(ReopenCourseHandler::class)->handle(new ReopenCourseCommand($courseId));
        }
        $this->upsertRiskCurriculum($courseId, $course->modules()[0]->id()->value());
        $this->upsertCoexistenceCurriculum($courseId, $this->stableId('MOD-RIESGO'));
        $this->upsertSelfCareCurriculum($courseId, $this->stableId('MOD-CONVIVENCIA'));
        $lessons = $this->lessons($competencyId, $preservedIds);
        $supplementalLessons = $this->supplementalLessons($competencyId);
        $advancedLessons = $this->advancedLessons($competencyId);
        foreach ($unitIds as $index => $unitId) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand(
                $courseId,
                $unitId,
                [$lessons[$index], $supplementalLessons[$index], $advancedLessons[$index]],
            ));
        }
        $this->replaceRiskContent($courseId, $riskCompetencyId);
        $this->replaceCoexistenceContent($courseId, $coexistenceCompetencyId);
        $this->replaceSelfCareContent($courseId, $selfCareCompetencyId);
        $this->synchronizeCourseMetadata($courseId, $course->modules()[0]->id()->value(), $unitIds);
        if (! DemoCoursePublication::isReady($courseId)) {
            return;
        }

        app(SubmitCourseForReviewHandler::class)->handle(new SubmitCourseForReviewCommand($courseId));
        app(ApproveCourseHandler::class)->handle(new ApproveCourseCommand($courseId));
        app(PublishCourseHandler::class)->handle(new PublishCourseCommand($courseId));
    }

    /** @param list<string> $firstModuleUnitIds */
    private function synchronizeCourseMetadata(string $courseId, string $firstModuleId, array $firstModuleUnitIds): void
    {
        DB::table('academic_courses')->where('id', $courseId)->update([
            'description' => 'Un recorrido integral para aprender a cruzar, percibir riesgos, interpretar señales y regular la atención en situaciones cotidianas de movilidad.',
            'objectives' => 'Elegir y ejecutar cruces seguros; anticipar peligros; interpretar señales y prioridades; actuar con convivencia, atención y autocuidado.',
            'duration_hours' => 7,
            'updated_at' => now(),
        ]);
        DB::table('academic_course_modules')->where('id', $firstModuleId)->update(['duration_minutes' => 120, 'updated_at' => now()]);
        foreach ([38, 38, 44] as $index => $minutes) {
            DB::table('academic_course_units')->where('id', $firstModuleUnitIds[$index])->update(['duration_minutes' => $minutes, 'updated_at' => now()]);
        }
    }

    private function competencyId(): string
    {
        $existing = DB::table('academic_competencies')->where('code', self::COMPETENCY_CODE)->first(['id']);
        if ($existing !== null) {
            return (string) $existing->id;
        }

        $competency = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand(
            self::COMPETENCY_CODE,
            'Cruza vías como peatón de manera segura',
            'Selecciona espacios apropiados, interpreta el entorno y ejecuta una secuencia de cruce que reduce el riesgo propio y de otras personas.',
            'vulnerable_road_users',
            'foundation',
        ));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($competency->id, 'PEATON.SECUENCIA', 'Aplica una secuencia segura de cruce'));

        $indicators = [
            ['PEATON.LUGAR', 'Selecciona una esquina, zona de paso marcada o paso peatonal con visibilidad adecuada.'],
            ['PEATON.PAUSA', 'Se detiene antes de ingresar a la zona destinada a vehículos.'],
            ['PEATON.OBSERVA', 'Mira, escucha y comprueba que no exista peligro antes de cruzar.'],
            ['PEATON.CRUZA', 'Cruza directamente, con calma, atención y sin distracciones.'],
        ];
        foreach ($indicators as [$code, $description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($competency->id, 'PEATON.SECUENCIA', $code, $description));
        }

        return $competency->id;
    }

    private function riskCompetencyId(): string
    {
        $existing = DB::table('academic_competencies')->where('code', self::RISK_COMPETENCY_CODE)->first(['id']);
        if ($existing !== null) {
            return (string) $existing->id;
        }

        $competency = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand(
            self::RISK_COMPETENCY_CODE,
            'Percibe y gestiona riesgos antes de actuar',
            'Detecta peligros, anticipa cambios y elige alternativas con margen de seguridad en distintos entornos viales.',
            'risk_management',
            'foundation',
        ));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($competency->id, 'RIESGO.ANTICIPA', 'Observa, anticipa y replantea decisiones viales'));

        foreach ([
            ['RIESGO.DETECTA', 'Identifica peligros visibles y ocultos antes de moverse.'],
            ['RIESGO.ANTICIPA', 'Explica qué podría cambiar o moverse en los próximos segundos.'],
            ['RIESGO.MARGEN', 'Elige una opción que permita tiempo y espacio para reaccionar.'],
            ['RIESGO.REPLANIFICA', 'Modifica su plan cuando cambian el clima, la visibilidad o el tránsito.'],
        ] as [$code, $description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($competency->id, 'RIESGO.ANTICIPA', $code, $description));
        }

        return $competency->id;
    }

    private function coexistenceCompetencyId(): string
    {
        $existing = DB::table('academic_competencies')->where('code', self::COEXISTENCE_COMPETENCY_CODE)->first(['id']);
        if ($existing !== null) {
            return (string) $existing->id;
        }

        $competency = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand(
            self::COEXISTENCE_COMPETENCY_CODE,
            'Convive y se comunica responsablemente en la vía',
            'Interpreta señales, prioridades y acuerdos viales para tomar decisiones previsibles, inclusivas y respetuosas.',
            'road_rules',
            'foundation',
        ));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($competency->id, 'CONVIVENCIA.INTERPRETA', 'Interpreta reglas y coopera con otras personas'));

        foreach ([
            ['CONVIVENCIA.SENAL', 'Reconoce la función de señales, marcas y semáforos en contexto.'],
            ['CONVIVENCIA.PRIORIDAD', 'Distingue prioridad de paso y comprobación de seguridad.'],
            ['CONVIVENCIA.COMUNICA', 'Actúa de forma visible, predecible y respetuosa.'],
            ['CONVIVENCIA.RESUELVE', 'Resuelve conflictos entre prisa, cortesía, señales y situación real.'],
        ] as [$code, $description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($competency->id, 'CONVIVENCIA.INTERPRETA', $code, $description));
        }

        return $competency->id;
    }

    private function selfCareCompetencyId(): string
    {
        $existing = DB::table('academic_competencies')->where('code', self::SELF_CARE_COMPETENCY_CODE)->first(['id']);
        if ($existing !== null) {
            return (string) $existing->id;
        }

        $competency = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand(
            self::SELF_CARE_COMPETENCY_CODE,
            'Regula su atención y estado antes de movilizarse',
            'Reconoce distracciones, emociones y presión social, y aplica estrategias de autocuidado antes de tomar decisiones viales.',
            'risk_management',
            'foundation',
        ));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($competency->id, 'AUTOCUIDADO.REGULA', 'Gestiona atención, emociones y presión social'));
        foreach ([
            ['AUTOCUIDADO.DISTRACTOR', 'Identifica distractores y los retira antes de entrar en una situación vial.'],
            ['AUTOCUIDADO.ESTADO', 'Reconoce cuándo su estado físico o emocional afecta una decisión.'],
            ['AUTOCUIDADO.PAUSA', 'Usa una pausa concreta para recuperar atención y control.'],
            ['AUTOCUIDADO.PRESION', 'Mantiene una decisión segura ante prisa o presión de otras personas.'],
        ] as [$code, $description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($competency->id, 'AUTOCUIDADO.REGULA', $code, $description));
        }

        return $competency->id;
    }

    private function selfCareModule(string $prerequisiteModuleId): CourseModuleInput
    {
        $moduleId = $this->stableId('MOD-AUTOCUIDADO');
        $unitIds = [$this->stableId('UNI-ATENCION'), $this->stableId('UNI-ESTADO'), $this->stableId('UNI-PRESION')];

        return new CourseModuleInput($moduleId, 'MISION-AUTOCUIDADO', 'Misión 4: Atención y autocuidado', 'Entrena la capacidad de detener impulsos y recuperar la atención antes de tomar una decisión vial.', 'Gestionar distractores, estados personales y presión social mediante estrategias prácticas.', 91, 4, [$prerequisiteModuleId], [
            new CourseUnitInput($unitIds[0], 'ATENCION-PRESENTE', '1. Tu atención tiene límites', 'Descubre cómo pantallas, ruido y conversaciones compiten por tu atención.', 'Retirar distractores antes de entrar en una situación vial.', 30, 1, []),
            new CourseUnitInput($unitIds[1], 'ESTADO-PERSONAL', '2. Cómo llegás también importa', 'Reconoce los efectos de prisa, enojo, cansancio y miedo.', 'Aplicar una pausa y pedir apoyo cuando el estado personal reduce la seguridad.', 30, 2, [$unitIds[0]]),
            new CourseUnitInput($unitIds[2], 'PRESION-DECISION', '3. Elegir aunque otras personas presionen', 'Practica respuestas ante el grupo y la urgencia.', 'Sostener una decisión segura y comunicarla sin conflicto.', 31, 3, [$unitIds[1]]),
        ]);
    }

    private function upsertSelfCareCurriculum(string $courseId, string $prerequisiteModuleId): void
    {
        $module = $this->selfCareModule($prerequisiteModuleId);
        $now = now();
        DB::table('academic_course_modules')->updateOrInsert(['id' => $module->id], [
            'course_id' => $courseId, 'code' => $module->code, 'title' => $module->title, 'description' => $module->description,
            'objectives' => $module->objectives, 'duration_minutes' => $module->durationMinutes, 'position' => $module->position,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        foreach ($module->units as $unit) {
            DB::table('academic_course_units')->updateOrInsert(['id' => $unit->id], [
                'module_id' => $module->id, 'code' => $unit->code, 'title' => $unit->title, 'description' => $unit->description,
                'objectives' => $unit->objectives, 'duration_minutes' => $unit->durationMinutes, 'position' => $unit->position,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        DB::table('academic_module_prerequisites')->updateOrInsert(['module_id' => $module->id, 'prerequisite_module_id' => $prerequisiteModuleId]);
        foreach ($module->units as $index => $unit) {
            if ($index > 0) {
                DB::table('academic_unit_prerequisites')->updateOrInsert(['unit_id' => $unit->id, 'prerequisite_unit_id' => $module->units[$index - 1]->id]);
            }
        }
    }

    private function replaceSelfCareContent(string $courseId, string $competencyId): void
    {
        $units = $this->selfCareModule('00000000-0000-0000-0000-000000000000')->units;
        $lessons = $this->selfCareLessons($competencyId);
        foreach ($units as $index => $unit) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($courseId, $unit->id, [$lessons[$index * 2], $lessons[$index * 2 + 1]]));
        }
    }

    /** @return list<LessonInput> */
    private function selfCareLessons(string $competencyId): array
    {
        return [
            $this->selfCareLesson('ATENCION-COMPITE', 'Reto 1: Una atención, muchas demandas', 1, 15, 'visual_exploration', 'Reconocer tareas que compiten con la observación vial.', $competencyId, ['AUTOCUIDADO.DISTRACTOR'], [
                $this->text(1, 'Mirar no siempre significa ver', 'La atención es limitada. Leer un mensaje, cambiar música o conversar intensamente consume capacidad para detectar movimiento. Esto afecta a peatones, ciclistas, pasajeros y conductores. **La distracción comienza antes de apartar completamente la mirada.**'),
                $this->scenario(2, 'Mensaje al acercarte al cruce', 'El teléfono vibra cuando estás a pocos pasos de la esquina.', '¿Cuándo conviene revisarlo?', [
                    ['id' => 'caminando', 'label' => 'Mientras caminás más despacio', 'feedback' => 'Caminar despacio no devuelve la atención que ocupa el mensaje.', 'correct' => false],
                    ['id' => 'protegido', 'label' => 'Después de detenerte en un lugar protegido y lejos del borde', 'feedback' => 'Correcto. Separás la comunicación de la decisión vial.', 'correct' => true],
                    ['id' => 'rapido', 'label' => 'Leerlo rápido antes de cruzar', 'feedback' => 'La prisa añade otra carga y puede ocultar cambios del entorno.', 'correct' => false],
                ]),
                $this->scenario(3, 'El mapa cambia la ruta', 'Luna sigue una aplicación de navegación. La pantalla le indica girar justo al llegar a una intersección que no conoce.', '¿Cómo separa la orientación de la decisión vial?', [
                    ['id' => 'seguir-pantalla', 'label' => 'Girar mientras mira la pantalla para no perder la indicación', 'feedback' => 'La navegación ocupa la atención necesaria para reconocer señales, actores y trayectorias.', 'correct' => false],
                    ['id' => 'detenerse', 'label' => 'Detenerse lejos del borde, revisar la ruta, guardar el teléfono y reevaluar', 'feedback' => 'Correcto. Primero resuelve la orientación en un espacio protegido y luego construye una decisión vial nueva.', 'correct' => true],
                    ['id' => 'copiar', 'label' => 'Seguir a otra persona que vaya en la misma dirección', 'feedback' => 'Otra persona puede tener otro destino o decidir con información diferente.', 'correct' => false],
                ]),
                $this->text(4, 'Estación de atención', 'Definí un lugar protegido donde sí podés usar una pantalla. Antes de retomar el recorrido: guardá el dispositivo, levantá la mirada, ubicá actores y volvé a construir tu plan.'),
            ]),
            $this->selfCareLesson('ATENCION-AUDITIVA', 'Reto 2: Escuchar también orienta', 2, 15, 'dilemma', 'Gestionar audífonos, ruido y conversaciones en zonas de decisión.', $competencyId, ['AUTOCUIDADO.DISTRACTOR', 'AUTOCUIDADO.PAUSA'], [
                $this->text(1, 'El sonido aporta pistas', 'Motores, bicicletas, alertas y voces pueden advertir movimientos fuera de tu campo visual. El oído no reemplaza la mirada, pero completa la información. Cerca de una vía, bajá el volumen o retirate los audífonos y pausá conversaciones que demandan atención.'),
                $this->scenario(2, 'Audífonos y autobús', 'Bajás del autobús con audífonos y querés cruzar por detrás mientras respondés una llamada.', '¿Cuál es la primera acción segura?', [
                    ['id' => 'seguir', 'label' => 'Seguir hablando y mirar rápidamente', 'feedback' => 'La conversación y el autobús mantienen dos fuentes de distracción y un punto ciego.', 'correct' => false],
                    ['id' => 'pausar', 'label' => 'Pausar la llamada, guardar los audífonos y buscar un punto visible', 'feedback' => 'Correcto. Recuperás atención antes de decidir.', 'correct' => true],
                    ['id' => 'correr', 'label' => 'Cruzar antes de que arranque el autobús', 'feedback' => 'La urgencia aumenta el riesgo en un espacio de poca visibilidad.', 'correct' => false],
                ]),
                $this->scenario(3, 'Mucho ruido en la calle', 'Una obra produce tanto ruido que Luna no distingue timbres de bicicleta, motores ni voces de advertencia.', '¿Cómo compensa la pérdida de información auditiva?', [
                    ['id' => 'oido', 'label' => 'Acercarse más para intentar escuchar mejor', 'feedback' => 'Acercarse al tránsito aumenta la exposición y el ruido puede seguir ocultando las alertas.', 'correct' => false],
                    ['id' => 'margen-visual', 'label' => 'Aumentar distancia, observación visual y margen, o elegir otro punto', 'feedback' => 'Correcto. Cuando un sentido aporta menos información, no se adivina: se aumenta la protección y se cambia el plan.', 'correct' => true],
                    ['id' => 'rapido', 'label' => 'Cruzar rápido para permanecer menos tiempo en el ruido', 'feedback' => 'La rapidez reduce el tiempo para detectar movimientos precisamente cuando falta información.', 'correct' => false],
                ]),
                $this->text(4, 'Ritual de recuperación', 'Practicá cuatro pasos: **pauso, guardo, levanto la mirada, escucho**. Solo después elegís dónde y cuándo avanzar.'),
            ]),
            $this->selfCareLesson('ESTADO-SEMAFORO', 'Reto 3: Tu semáforo interior', 1, 15, 'story', 'Reconocer señales personales que requieren detenerse o pedir apoyo.', $competencyId, ['AUTOCUIDADO.ESTADO', 'AUTOCUIDADO.PAUSA'], [
                $this->text(1, 'El estado personal cambia el riesgo', 'Cansancio, enojo, ansiedad, miedo o exceso de confianza pueden estrechar la atención y acelerar decisiones. Usá un semáforo interior: **verde**, estoy presente; **amarillo**, necesito una pausa; **rojo**, necesito detenerme o pedir apoyo.'),
                $this->scenario(2, 'Saliste con enojo', 'Después de una discusión salís caminando rápido y casi no recordás el último tramo recorrido.', '¿Qué indica esa señal?', [
                    ['id' => 'normal', 'label' => 'Que podés continuar porque conocés el camino', 'feedback' => 'La familiaridad no compensa la atención reducida.', 'correct' => false],
                    ['id' => 'pausa', 'label' => 'Que necesitás detenerte en un lugar seguro y recuperar atención', 'feedback' => 'Correcto. Reconocés el estado antes de llegar a otra decisión vial.', 'correct' => true],
                    ['id' => 'rapido', 'label' => 'Que conviene llegar más rápido', 'feedback' => 'Aumentar la velocidad reduce todavía más el tiempo para reaccionar.', 'correct' => false],
                ]),
                $this->scenario(3, 'Miedo ante una intersección nueva', 'Luna llega a una intersección grande, siente ansiedad y no logra organizar toda la información que ve.', '¿Qué respuesta convierte el miedo en autocuidado?', [
                    ['id' => 'obligarse', 'label' => 'Cruzar de inmediato para demostrar que puede', 'feedback' => 'Forzarse bajo presión puede aumentar la ansiedad y reducir todavía más la atención.', 'correct' => false],
                    ['id' => 'pausa-apoyo', 'label' => 'Alejarse del borde, respirar, observar por partes y pedir apoyo si lo necesita', 'feedback' => 'Correcto. Reconoce su estado y recupera capacidad antes de decidir.', 'correct' => true],
                    ['id' => 'ojos', 'label' => 'Cerrar los ojos unos segundos y seguir al grupo', 'feedback' => 'Seguir al grupo sin reconstruir la información entrega la decisión a otras personas.', 'correct' => false],
                ]),
                $this->text(4, 'Chequeo de diez segundos', 'Preguntate: ¿dónde estoy?, ¿qué siento?, ¿qué estoy apurando?, ¿puedo observar bien?, ¿necesito compañía? Pedir apoyo también es una habilidad vial.'),
            ]),
            $this->selfCareLesson('CANSANCIO-PRISA', 'Reto 4: Cuando el cuerpo pide margen', 2, 15, 'dilemma', 'Modificar el plan ante cansancio o urgencia.', $competencyId, ['AUTOCUIDADO.ESTADO', 'AUTOCUIDADO.PAUSA'], [
                $this->text(1, 'La prisa fabrica atajos', 'Cuando llegamos tarde o estamos cansados, el cerebro acepta opciones que normalmente descartaría. Prepararse con tiempo ayuda, pero si la urgencia ya existe, la respuesta segura es reducir decisiones simultáneas y elegir una alternativa recuperable.'),
                $this->scenario(2, 'Llegar tarde a clase', 'Vas tarde y la ruta segura tarda cinco minutos más que un atajo sin acera.', '¿Qué decisión protege tu futuro?', [
                    ['id' => 'atajo', 'label' => 'Tomar el atajo solo esta vez', 'feedback' => 'La urgencia no mejora las condiciones del atajo.', 'correct' => false],
                    ['id' => 'segura', 'label' => 'Usar la ruta segura y avisar que llegarás tarde', 'feedback' => 'Correcto. Convertís un problema de horario en una consecuencia manejable.', 'correct' => true],
                    ['id' => 'correr', 'label' => 'Correr por la ruta segura', 'feedback' => 'La infraestructura ayuda, pero correr puede reducir observación y estabilidad.', 'correct' => false],
                ]),
                $this->scenario(3, 'Cansancio al final del día', 'Después de un día largo, una persona adulta nota que bosteza, pierde detalles del recorrido y debe acompañar a una niña hasta casa.', '¿Cuál es un plan responsable?', [
                    ['id' => 'esfuerzo', 'label' => 'Continuar igual y esforzarse por prestar más atención', 'feedback' => 'La voluntad no elimina las señales de fatiga ni garantiza atención sostenida.', 'correct' => false],
                    ['id' => 'reducir', 'label' => 'Detenerse en un lugar seguro, descansar y buscar apoyo o una ruta más simple', 'feedback' => 'Correcto. Adaptar el plan protege tanto a la persona adulta como a quien acompaña.', 'correct' => true],
                    ['id' => 'nina-guia', 'label' => 'Pedir a la niña que tome todas las decisiones del recorrido', 'feedback' => 'Una niña puede participar, pero no debe recibir sola una responsabilidad que corresponde a la persona adulta.', 'correct' => false],
                ]),
                $this->text(4, 'Plan B antes de salir', 'Prepará alternativas: a quién avisar, dónde esperar, qué ruta usar y cuándo pedir acompañamiento. Un plan B reduce la presión de improvisar.'),
            ]),
            $this->selfCareLesson('PRESION-GRUPO', 'Reto 5: Mi decisión no necesita aplausos', 1, 16, 'dilemma', 'Mantener límites seguros ante presión o burla.', $competencyId, ['AUTOCUIDADO.PRESION', 'AUTOCUIDADO.PAUSA'], [
                $this->text(1, 'Pertenecer sin imitar el riesgo', 'La presión puede ser directa —“¡animate!”— o silenciosa, cuando seguimos al grupo por no quedarnos atrás. Una frase breve evita discutir: **“Yo espero; nos vemos al otro lado”**. No hace falta convencer a todo el grupo para cuidarte.'),
                $this->scenario(2, 'El grupo cruza corriendo', 'Tus amistades cruzan fuera del paso y te llaman desde el otro lado.', '¿Qué respuesta sostiene una decisión segura?', [
                    ['id' => 'seguir', 'label' => 'Seguirlas para no quedarte solo', 'feedback' => 'El número de personas no transforma una acción riesgosa en segura.', 'correct' => false],
                    ['id' => 'limite', 'label' => 'Decir que usarás el cruce seguro y encontrarlas después', 'feedback' => 'Correcto. Comunicás un límite claro sin entrar en conflicto.', 'correct' => true],
                    ['id' => 'discutir', 'label' => 'Discutir desde el borde de la calle', 'feedback' => 'La discusión mantiene tu atención y tu cuerpo cerca del peligro.', 'correct' => false],
                ]),
                $this->scenario(3, 'Una persona adulta tiene prisa', 'Una persona adulta toma a Luna de la mano para cruzar, pero Luna ve una motocicleta que la persona adulta parece no haber notado.', '¿Qué puede hacer Luna?', [
                    ['id' => 'obedecer', 'label' => 'Seguir sin decir nada porque la persona adulta decide', 'feedback' => 'El acompañamiento es importante, pero cualquier persona puede detectar información que otra no vio.', 'correct' => false],
                    ['id' => 'alertar', 'label' => 'Detenerse y decir claramente: “esperá, viene una motocicleta”', 'feedback' => 'Correcto. Comunicar un peligro no es desobedecer: es participar en una decisión compartida y segura.', 'correct' => true],
                    ['id' => 'soltarse', 'label' => 'Soltarse y correr hacia atrás sin avisar', 'feedback' => 'Un movimiento repentino puede crear otro riesgo. Es mejor detener y comunicar con claridad.', 'correct' => false],
                ]),
                $this->text(4, 'Ensayo de frases', 'Practicá respuestas sencillas: “yo espero”, “guardemos el teléfono”, “por aquí hay más visibilidad” y “prefiero llegar tarde que cruzar sin margen”.'),
            ]),
            $this->selfCareLesson('AUTOCUIDADO-MISION', 'Misión integradora: recuperá el control', 2, 15, 'competency_challenge', 'Integrar atención, estado personal, pausa y respuesta a la presión.', $competencyId, ['AUTOCUIDADO.DISTRACTOR', 'AUTOCUIDADO.ESTADO', 'AUTOCUIDADO.PAUSA', 'AUTOCUIDADO.PRESION'], [
                $this->text(1, 'Secuencia de autocuidado', '**Reconocé → detente en un lugar protegido → retirá el distractor → regulá tu estado → reconstruí el plan.** Si todavía no estás listo, pedí apoyo o posponé la acción.'),
                $this->scenario(2, 'Mensaje, lluvia y prisa', 'Llueve, vas tarde y recibís un mensaje urgente justo antes del cruce.', '¿Qué hacés primero?', [
                    ['id' => 'leer', 'label' => 'Leer mientras esperás la señal', 'feedback' => 'Aunque estés quieto, necesitás atención para observar los cambios del cruce.', 'correct' => false],
                    ['id' => 'separar', 'label' => 'Alejarte del borde, resolver el mensaje y luego reevaluar', 'feedback' => 'Correcto. Separás tareas y reconstruís la decisión con atención.', 'correct' => true],
                    ['id' => 'correr', 'label' => 'Cruzar rápido y responder después', 'feedback' => 'La combinación de lluvia y prisa necesita más margen, no menos.', 'correct' => false],
                ]),
                $this->scenario(3, 'Cansancio al pedalear', 'Estás cansado, perdés concentración y el grupo quiere continuar rápido en bicicleta.', '¿Qué decisión es responsable?', [
                    ['id' => 'ritmo', 'label' => 'Mantener el ritmo para no atrasar al grupo', 'feedback' => 'La presión no cambia las señales de tu cuerpo.', 'correct' => false],
                    ['id' => 'parar', 'label' => 'Comunicarlo y detenerte en un lugar seguro', 'feedback' => 'Correcto. Reconocés tu estado y pedís una adaptación.', 'correct' => true],
                    ['id' => 'silencio', 'label' => 'No decir nada y concentrarte más', 'feedback' => 'La voluntad no siempre compensa el cansancio físico.', 'correct' => false],
                ]),
                $this->scenario(4, 'Burla por esperar', 'Otra persona se burla porque no cruzás durante una oportunidad estrecha.', '¿Qué respuesta conserva control?', [
                    ['id' => 'probar', 'label' => 'Cruzar para demostrar que podés', 'feedback' => 'La demostración entrega tu decisión a la presión externa.', 'correct' => false],
                    ['id' => 'esperar', 'label' => 'Mantenerte protegido y responder con una frase breve', 'feedback' => 'Correcto. Tu límite no necesita aprobación.', 'correct' => true],
                    ['id' => 'empujar', 'label' => 'Responder con enojo y acercarte', 'feedback' => 'El conflicto añade distracción y puede acercarte al peligro.', 'correct' => false],
                ]),
                $this->text(5, 'Transferencia personal y Pasaporte Vial', "Elegí dos señales internas que vas a vigilar y escribí tu protocolo: dónde te detenés, qué distractor retirás, cómo recuperás atención y a quién pedís ayuda. No incluyás información privada.\n\nDespués realizá una práctica apropiada para tu edad: las personas menores deben hacerla con acompañamiento adulto. Registrá una reflexión sobre qué señal interna apareció y cómo cambiaste el plan. La finalización digital, la práctica observada y la reflexión quedan como evidencias diferentes en tu **Pasaporte Vial**."),
            ]),
        ];
    }

    /** @param list<string> $indicators @param list<ContentBlockInput> $blocks */
    private function selfCareLesson(string $code, string $title, int $position, int $minutes, string $experience, string $objective, string $competencyId, array $indicators, array $blocks): LessonInput
    {
        return $this->lesson($code, $title, $position, $minutes, $experience, $objective, $competencyId, $indicators, $blocks, null, 'AUTOCUIDADO.REGULA');
    }

    private function coexistenceModule(string $prerequisiteModuleId): CourseModuleInput
    {
        $moduleId = $this->stableId('MOD-CONVIVENCIA');
        $unitIds = [$this->stableId('UNI-LENGUAJE-VIAL'), $this->stableId('UNI-PRIORIDADES'), $this->stableId('UNI-COOPERACION')];

        return new CourseModuleInput($moduleId, 'MISION-CONVIVENCIA', 'Misión 3: Señales para convivir', 'Descubre cómo las señales y los acuerdos permiten compartir la vía sin depender de la memoria o la imposición.', 'Interpretar el lenguaje vial, comprobar prioridades y actuar de forma predecible y solidaria.', 93, 3, [$prerequisiteModuleId], [
            new CourseUnitInput($unitIds[0], 'LENGUAJE-VIAL', '1. La vía nos habla', 'Interpreta formas, colores, marcas y señales según su función.', 'Comprender qué información aporta cada familia de señales.', 31, 1, []),
            new CourseUnitInput($unitIds[1], 'PRIORIDAD-CUIDADO', '2. Prioridad no significa invulnerabilidad', 'Relaciona reglas de prioridad con observación y cuidado.', 'Aplicar prioridades sin dejar de comprobar peligros reales.', 31, 2, [$unitIds[0]]),
            new CourseUnitInput($unitIds[2], 'COOPERACION-VIAL', '3. Movimientos que otras personas comprenden', 'Practica comunicación, paciencia e inclusión.', 'Resolver situaciones compartidas de manera predecible y respetuosa.', 31, 3, [$unitIds[1]]),
        ]);
    }

    private function upsertCoexistenceCurriculum(string $courseId, string $prerequisiteModuleId): void
    {
        $module = $this->coexistenceModule($prerequisiteModuleId);
        $now = now();
        DB::table('academic_course_modules')->updateOrInsert(['id' => $module->id], [
            'course_id' => $courseId, 'code' => $module->code, 'title' => $module->title,
            'description' => $module->description, 'objectives' => $module->objectives,
            'duration_minutes' => $module->durationMinutes, 'position' => $module->position,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        foreach ($module->units as $unit) {
            DB::table('academic_course_units')->updateOrInsert(['id' => $unit->id], [
                'module_id' => $module->id, 'code' => $unit->code, 'title' => $unit->title,
                'description' => $unit->description, 'objectives' => $unit->objectives,
                'duration_minutes' => $unit->durationMinutes, 'position' => $unit->position,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        DB::table('academic_module_prerequisites')->updateOrInsert(['module_id' => $module->id, 'prerequisite_module_id' => $prerequisiteModuleId]);
        foreach ($module->units as $index => $unit) {
            if ($index > 0) {
                DB::table('academic_unit_prerequisites')->updateOrInsert(['unit_id' => $unit->id, 'prerequisite_unit_id' => $module->units[$index - 1]->id]);
            }
        }
    }

    private function replaceCoexistenceContent(string $courseId, string $competencyId): void
    {
        $units = $this->coexistenceModule('00000000-0000-0000-0000-000000000000')->units;
        $lessons = $this->coexistenceLessons($competencyId);
        foreach ($units as $index => $unit) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($courseId, $unit->id, [$lessons[$index * 2], $lessons[$index * 2 + 1]]));
        }
    }

    /** @return list<LessonInput> */
    private function coexistenceLessons(string $competencyId): array
    {
        return [
            $this->coexistenceLesson('SENALES-FUNCION', 'Reto 1: No memoricés, interpretá', 1, 15, 'visual_exploration', 'Clasificar señales por la decisión que ayudan a tomar.', $competencyId, ['CONVIVENCIA.SENAL'], [
                $this->text(1, 'Un lenguaje compartido', "Las señales no son decoración ni una colección para memorizar. **Advierten peligros, establecen obligaciones o prohibiciones y orientan el recorrido**. Sus formas y colores ayudan a reconocer rápidamente su función, aunque el diseño exacto pueda variar entre países.\n\nEn Costa Rica conviven señales verticales, semáforos y marcas pintadas sobre la vía."),
                $this->scenario(2, 'Señal desconocida', 'Ves una señal que no recordás, pero su forma y ubicación advierten sobre un cambio en el camino.', '¿Qué respuesta demuestra comprensión?', [
                    ['id' => 'ignorar', 'label' => 'Ignorarla porque no sabés su nombre', 'feedback' => 'No recordar el nombre no elimina la información de advertencia.', 'correct' => false],
                    ['id' => 'precaucion', 'label' => 'Reducir la prisa, observar el contexto y actuar con precaución', 'feedback' => 'Correcto. Interpretás su función y buscás información antes de actuar.', 'correct' => true],
                    ['id' => 'copiar', 'label' => 'Copiar lo que haga la persona de adelante', 'feedback' => 'Otra persona puede equivocarse o tener información diferente.', 'correct' => false],
                ]),
                $this->scenario(3, 'Conos frente a la escuela', 'Unos conos y una señal temporal desvían a las personas de la acera habitual frente a una escuela.', '¿Cómo se interpreta correctamente esta información?', [
                    ['id' => 'decoracion', 'label' => 'Los conos son decoración y la ruta habitual sigue igual', 'feedback' => 'La señalización temporal comunica un cambio actual, aunque el recorrido normalmente sea distinto.', 'correct' => false],
                    ['id' => 'cambio', 'label' => 'Hay una condición temporal; se debe seguir la ruta protegida y observar indicaciones', 'feedback' => 'Correcto. Interpretás la función de la señal y confirmás cómo continuar sin invadir la calzada.', 'correct' => true],
                    ['id' => 'entre-conos', 'label' => 'Pasar entre los conos si queda espacio', 'feedback' => 'Los conos delimitan una zona que puede contener trabajos, maquinaria u otros peligros no visibles.', 'correct' => false],
                ]),
                $this->text(4, 'Laboratorio de funciones', 'Buscá señales en imágenes seguras y agrupálas por pregunta: ¿me advierte?, ¿me indica una obligación?, ¿me orienta? Explicá la función con tus palabras antes de buscar el nombre oficial.'),
            ]),
            $this->coexistenceLesson('MARCAS-SEMAFOROS', 'Reto 2: Capas de información', 2, 16, 'dilemma', 'Combinar semáforos, marcas viales y entorno sin atender una sola pista.', $competencyId, ['CONVIVENCIA.SENAL', 'CONVIVENCIA.RESUELVE'], [
                $this->text(1, 'Una señal nunca está sola', "El semáforo indica turnos, las marcas delimitan espacios y las señales verticales agregan reglas o advertencias. También importa la realidad: una obstrucción, una persona que no te vio o un vehículo de emergencia pueden exigir esperar.\n\n**Permiso no significa ausencia automática de peligro.**"),
                $this->scenario(2, 'Luz favorable, cruce ocupado', 'La señal permite avanzar, pero una persona mayor todavía está terminando de cruzar.', '¿Qué acción favorece la convivencia?', [
                    ['id' => 'avanzar', 'label' => 'Avanzar porque ya cambió la señal', 'feedback' => 'La luz organiza turnos, pero no justifica poner presión sobre quien sigue expuesto.', 'correct' => false],
                    ['id' => 'esperar', 'label' => 'Esperar hasta que la persona llegue a un espacio protegido', 'feedback' => 'Correcto. Combinás la señal con el cuidado de la situación real.', 'correct' => true],
                    ['id' => 'sonar', 'label' => 'Usar una señal sonora para que se apresure', 'feedback' => 'La presión puede provocar una caída o una decisión insegura.', 'correct' => false],
                ]),
                $this->scenario(3, 'Señales que parecen contradecirse', 'El semáforo vehicular está en rojo, pero la señal peatonal todavía no autoriza el cruce y un vehículo puede girar desde otra vía.', '¿Qué información debe seguir Luna?', [
                    ['id' => 'rojo-carros', 'label' => 'Cruzar porque el rojo detuvo a todos los vehículos', 'feedback' => 'El rojo visible puede corresponder solo a un movimiento; además, la señal peatonal aún no autoriza avanzar.', 'correct' => false],
                    ['id' => 'esperar-peatonal', 'label' => 'Esperar la señal peatonal y comprobar también los giros', 'feedback' => 'Correcto. Se combinan semáforo, trayectorias y situación real antes de decidir.', 'correct' => true],
                    ['id' => 'grupo', 'label' => 'Cruzar si otra persona empieza primero', 'feedback' => 'La decisión de otra persona no aclara qué señales ni movimientos está observando.', 'correct' => false],
                ]),
                $this->text(4, 'Lectura en capas', 'En una imagen vial, señalá por separado: semáforo, marcas, señal vertical, actores y peligro actual. Luego construí una sola decisión usando las cinco capas.'),
            ]),
            $this->coexistenceLesson('PRIORIDAD-COMPRUEBA', 'Reto 3: Tener prioridad y seguir cuidándote', 1, 15, 'story', 'Distinguir el derecho de paso de la comprobación de seguridad.', $competencyId, ['CONVIVENCIA.PRIORIDAD'], [
                $this->text(1, 'La prioridad organiza, no crea un escudo', 'Las reglas de prioridad ayudan a decidir quién pasa primero. Sin embargo, una persona puede distraerse, interpretar mal o no verte. Ejercer una prioridad con seguridad significa **hacerte visible, observar y confirmar**.'),
                $this->scenario(2, 'Paso peatonal', 'Estás junto al paso peatonal. Un automóvil se aproxima y todavía no reduce claramente la velocidad.', '¿Qué decisión integra prioridad y autoprotección?', [
                    ['id' => 'entrar', 'label' => 'Entrar para obligarlo a detenerse', 'feedback' => 'Tener prioridad no vuelve segura una trayectoria que aún no está controlada.', 'correct' => false],
                    ['id' => 'confirmar', 'label' => 'Esperar y confirmar que se detenga antes de cruzar', 'feedback' => 'Correcto. Conservás tu prioridad sin depender de una reacción incierta.', 'correct' => true],
                    ['id' => 'renunciar', 'label' => 'No volver a usar pasos peatonales', 'feedback' => 'La solución es comprobar, no abandonar la infraestructura prevista.', 'correct' => false],
                ]),
                $this->scenario(3, 'Cruce de ciclovía', 'Luna necesita atravesar una ciclovía para llegar a la parada. Una bicicleta se aproxima y su velocidad no es clara.', '¿Qué conducta permite compartir el espacio?', [
                    ['id' => 'peaton', 'label' => 'Avanzar porque una persona a pie siempre tiene prioridad', 'feedback' => 'La prioridad depende del lugar y nunca elimina la necesidad de observar una trayectoria activa.', 'correct' => false],
                    ['id' => 'comprobar', 'label' => 'Detenerse antes de la ciclovía, comprobar y cruzar sin quedarse en ella', 'feedback' => 'Correcto. Luna reconoce el espacio de circulación y actúa de forma visible y predecible.', 'correct' => true],
                    ['id' => 'detener-bici', 'label' => 'Pararse dentro de la ciclovía para pedir que se detenga', 'feedback' => 'Ocupar la trayectoria reduce el margen de ambas personas y vuelve confuso el encuentro.', 'correct' => false],
                ]),
                $this->text(4, 'Dos frases verdaderas', 'Completá: “La regla indica que…”, y luego: “La situación real me exige comprobar que…”. Esta separación evita confundir una norma con una garantía física.'),
            ]),
            $this->coexistenceLesson('CORTESIA-SEGURA', 'Reto 4: Cuando la cortesía confunde', 2, 16, 'dilemma', 'Evaluar gestos informales sin asumir que controlan toda la vía.', $competencyId, ['CONVIVENCIA.PRIORIDAD', 'CONVIVENCIA.COMUNICA'], [
                $this->text(1, 'Un gesto no controla el entorno', 'Una persona puede ceder el paso con la mano, pero no controla otros carriles, bicicletas, motocicletas ni vehículos que giran. Agradecer la cortesía está bien; actuar sin comprobar, no. Tus propios movimientos deben ser claros y previsibles.'),
                $this->scenario(2, 'Te hacen una señal', 'Una persona conductora se detiene y te invita a cruzar, pero el carril contiguo permanece oculto.', '¿Qué hacés?', [
                    ['id' => 'cruzar', 'label' => 'Cruzar de inmediato para no ser descortés', 'feedback' => 'La presión social no debe sustituir la comprobación del carril oculto.', 'correct' => false],
                    ['id' => 'comprobar', 'label' => 'Agradecer y comprobar todos los movimientos antes de avanzar', 'feedback' => 'Correcto. Comunicás respeto sin entregar tu seguridad a un gesto.', 'correct' => true],
                    ['id' => 'espalda', 'label' => 'Dar la espalda al tránsito mientras decidís', 'feedback' => 'Perderías información sobre los cambios del entorno.', 'correct' => false],
                ]),
                $this->scenario(3, 'Ceder en una esquina estrecha', 'Una persona ciclista reduce la velocidad para dejar pasar a Luna, pero detrás se acerca silenciosamente otra bicicleta.', '¿Cómo responde Luna sin convertir la cortesía en riesgo?', [
                    ['id' => 'rapido', 'label' => 'Cruzar rápido para agradecer el gesto', 'feedback' => 'La prisa responde a la presión social, pero ignora una segunda trayectoria activa.', 'correct' => false],
                    ['id' => 'revisar', 'label' => 'Agradecer, permanecer protegida y comprobar todo el espacio antes de cruzar', 'feedback' => 'Correcto. La cortesía se reconoce, pero la decisión sigue basada en una comprobación completa.', 'correct' => true],
                    ['id' => 'espalda', 'label' => 'Mirar solo a quien cedió el paso', 'feedback' => 'Fijarse en una sola persona puede ocultar movimientos que esa persona no controla.', 'correct' => false],
                ]),
                $this->text(4, 'Comunicación predecible', 'Practicá tres conductas: detenerte en un lugar visible, orientar el cuerpo hacia tu recorrido y evitar cambios repentinos. No necesitás contacto visual prolongado: necesitás evidencia de que el movimiento está controlado.'),
            ]),
            $this->coexistenceLesson('ESPACIO-COMPARTIDO', 'Reto 5: La vía también es de otras personas', 1, 16, 'community_observation', 'Reconocer necesidades distintas y evitar conductas que excluyen.', $competencyId, ['CONVIVENCIA.COMUNICA'], [
                $this->text(1, 'Convivir es dejar espacio', "Una acera bloqueada, una rampa ocupada o una bicicleta estacionada en un paso puede obligar a alguien a exponerse. Niñas, niños, personas mayores y quienes usan apoyos de movilidad pueden requerir más tiempo o espacio.\n\nLa convivencia vial se mide también por los obstáculos que decidimos **no crear**."),
                $this->scenario(2, 'Esperar sin bloquear', 'Un grupo espera el autobús ocupando toda la acera. Se acerca una persona con coche infantil.', '¿Qué respuesta cuida el espacio compartido?', [
                    ['id' => 'seguir', 'label' => 'Mantenerse porque llegaron primero', 'feedback' => 'Llegar primero no convierte el espacio común en espacio exclusivo.', 'correct' => false],
                    ['id' => 'abrir', 'label' => 'Reorganizarse y dejar un paso continuo y protegido', 'feedback' => 'Correcto. La acción es clara, inclusiva y reduce exposición.', 'correct' => true],
                    ['id' => 'calzada', 'label' => 'Indicarle que rodee por la calzada', 'feedback' => 'Eso trasladaría el riesgo a quien necesita pasar.', 'correct' => false],
                ]),
                $this->scenario(3, 'Ayuda con respeto', 'Una persona con bastón blanco espera cerca de un cruce. Luna piensa que quizá necesita apoyo.', '¿Cómo puede colaborar de forma respetuosa?', [
                    ['id' => 'tomar', 'label' => 'Tomarla del brazo y guiarla sin avisar', 'feedback' => 'El contacto inesperado puede desorientar y no respeta la autonomía de la persona.', 'correct' => false],
                    ['id' => 'ofrecer', 'label' => 'Presentarse, ofrecer ayuda y seguir las indicaciones de la persona', 'feedback' => 'Correcto. La colaboración comienza preguntando y permite que la persona decida qué apoyo necesita.', 'correct' => true],
                    ['id' => 'decidir', 'label' => 'Elegir por ella cuándo y por dónde cruzar', 'feedback' => 'Apoyar no significa sustituir sus decisiones. La información y el consentimiento guían la ayuda.', 'correct' => false],
                ]),
                $this->text(4, 'Auditoría amable', 'Observá un espacio público sin registrar personas ni direcciones. Identificá una conducta que facilita el paso y una que lo dificulta. Proponé una mejora que no dependa de culpar a alguien.'),
            ]),
            $this->coexistenceLesson('CONVIVENCIA-MISION', 'Misión integradora: acuerdos que protegen', 2, 15, 'competency_challenge', 'Integrar señales, prioridades, comunicación e inclusión.', $competencyId, ['CONVIVENCIA.SENAL', 'CONVIVENCIA.PRIORIDAD', 'CONVIVENCIA.COMUNICA', 'CONVIVENCIA.RESUELVE'], [
                $this->text(1, 'Tu criterio de convivencia', 'Interpretá la información, reconocé el turno, comprobá la situación real, comunicá movimientos previsibles y dejá espacio para otras personas. **La meta no es ganar el paso: es que todas las personas lleguen seguras.**'),
                $this->scenario(2, 'Cruce con información contradictoria', 'La señal favorece tu paso, alguien te hace un gesto para avanzar y una motocicleta aparece por un espacio oculto.', '¿Qué decisión integra las señales y la realidad?', [
                    ['id' => 'permiso', 'label' => 'Avanzar por tener señal y gesto favorables', 'feedback' => 'Dos permisos no eliminan un peligro real que ya identificaste.', 'correct' => false],
                    ['id' => 'esperar', 'label' => 'Mantenerte protegido hasta que el movimiento esté controlado', 'feedback' => 'Correcto. Priorizás la situación real y conservás una decisión predecible.', 'correct' => true],
                    ['id' => 'competir', 'label' => 'Avanzar rápido antes que la motocicleta', 'feedback' => 'Competir elimina el margen y convierte el encuentro en conflicto.', 'correct' => false],
                ]),
                $this->scenario(3, 'Semáforo fuera de servicio', 'Un semáforo no funciona y varias personas intentan avanzar al mismo tiempo.', '¿Qué principio ayuda más?', [
                    ['id' => 'primero', 'label' => 'Pasar primero para salir del problema', 'feedback' => 'La prisa aumenta la incertidumbre para todas las personas.', 'correct' => false],
                    ['id' => 'cautela', 'label' => 'Detenerse, observar, aplicar reglas conocidas y ceder ante la duda', 'feedback' => 'Correcto. Reducís velocidad, comunicás y resolvés la falta de una señal activa.', 'correct' => true],
                    ['id' => 'copiar', 'label' => 'Seguir al vehículo más grande', 'feedback' => 'El tamaño no concede seguridad ni prioridad automática.', 'correct' => false],
                ]),
                $this->scenario(4, 'Paso ocupado', 'La acera y la rampa están parcialmente bloqueadas durante una actividad comunitaria.', '¿Qué solución expresa ciudadanía vial?', [
                    ['id' => 'rodear', 'label' => 'Pedir que cada persona rodee como pueda', 'feedback' => 'Eso distribuye el riesgo de forma injusta y puede excluir.', 'correct' => false],
                    ['id' => 'despejar', 'label' => 'Despejar una ruta continua, visible y accesible', 'feedback' => 'Correcto. La organización colectiva protege a personas con necesidades distintas.', 'correct' => true],
                    ['id' => 'esperar', 'label' => 'Mantener el bloqueo hasta terminar la actividad', 'feedback' => 'Una actividad no debe anular el tránsito seguro de otras personas.', 'correct' => false],
                ]),
                $this->text(5, 'Compromiso transferible', 'Explicá una regla vial con tus palabras y agregá: qué riesgo ayuda a reducir, a quién protege y qué harías si la situación real cambia. Ese razonamiento vale más que repetir el nombre de una señal.'),
            ]),
        ];
    }

    /** @param list<string> $indicators @param list<ContentBlockInput> $blocks */
    private function coexistenceLesson(string $code, string $title, int $position, int $minutes, string $experience, string $objective, string $competencyId, array $indicators, array $blocks): LessonInput
    {
        return $this->lesson($code, $title, $position, $minutes, $experience, $objective, $competencyId, $indicators, $blocks, null, 'CONVIVENCIA.INTERPRETA');
    }

    private function riskModule(string $prerequisiteModuleId): CourseModuleInput
    {
        $moduleId = $this->stableId('MOD-RIESGO');
        $unitIds = [$this->stableId('UNI-RADAR'), $this->stableId('UNI-ANTICIPA'), $this->stableId('UNI-DECIDE')];

        return new CourseModuleInput($moduleId, 'MISION-RIESGO', 'Misión 2: Detectives del riesgo', 'Aprende a descubrir peligros antes de que se conviertan en una emergencia.', 'Observar, anticipar y elegir decisiones con suficiente margen de seguridad.', 90, 2, [$prerequisiteModuleId], [
            new CourseUnitInput($unitIds[0], 'RADAR-RIESGO', '1. Activa tu radar', 'Diferencia peligro, riesgo y consecuencia.', 'Detectar señales de peligro visibles y ocultas.', 30, 1, []),
            new CourseUnitInput($unitIds[1], 'ANTICIPA-CAMBIOS', '2. Piensa unos segundos adelante', 'Anticipa movimientos y cambios del entorno.', 'Predecir posibilidades sin asumir que las demás personas actuarán siempre bien.', 30, 2, [$unitIds[0]]),
            new CourseUnitInput($unitIds[2], 'DECIDE-MARGEN', '3. Elige con margen', 'Compara alternativas y replantea el recorrido.', 'Elegir la opción que deja tiempo, espacio y una salida segura.', 30, 3, [$unitIds[1]]),
        ]);
    }

    private function upsertRiskCurriculum(string $courseId, string $prerequisiteModuleId): void
    {
        $module = $this->riskModule($prerequisiteModuleId);
        $now = now();
        DB::table('academic_course_modules')->updateOrInsert(['id' => $module->id], [
            'course_id' => $courseId, 'code' => $module->code, 'title' => $module->title,
            'description' => $module->description, 'objectives' => $module->objectives,
            'duration_minutes' => $module->durationMinutes, 'position' => $module->position,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        foreach ($module->units as $unit) {
            DB::table('academic_course_units')->updateOrInsert(['id' => $unit->id], [
                'module_id' => $module->id, 'code' => $unit->code, 'title' => $unit->title,
                'description' => $unit->description, 'objectives' => $unit->objectives,
                'duration_minutes' => $unit->durationMinutes, 'position' => $unit->position,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        DB::table('academic_module_prerequisites')->updateOrInsert([
            'module_id' => $module->id, 'prerequisite_module_id' => $prerequisiteModuleId,
        ]);
        foreach ($module->units as $index => $unit) {
            if ($index > 0) {
                DB::table('academic_unit_prerequisites')->updateOrInsert([
                    'unit_id' => $unit->id, 'prerequisite_unit_id' => $module->units[$index - 1]->id,
                ]);
            }
        }
    }

    private function replaceRiskContent(string $courseId, string $competencyId): void
    {
        $units = $this->riskModule('00000000-0000-0000-0000-000000000000')->units;
        $lessons = $this->riskLessons($competencyId);
        foreach ($units as $index => $unit) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($courseId, $unit->id, [$lessons[$index * 2], $lessons[$index * 2 + 1]]));
        }
    }

    /** @return list<LessonInput> */
    private function riskLessons(string $competencyId): array
    {
        return [
            $this->riskLesson('RIESGO-PISTAS', 'Reto 1: Lee las pistas', 1, 15, 'visual_exploration', 'Distinguir un peligro de la posibilidad de sufrir daño.', $competencyId, ['RIESGO.DETECTA'], [
                $this->text(1, 'Peligro no es lo mismo que riesgo', "Un **peligro** es algo capaz de causar daño: una curva sin visibilidad, una pelota cerca de la calle o aceite en el pavimento. El **riesgo** combina ese peligro con la posibilidad de que alguien quede expuesto.\n\nNo buscamos sentir miedo; buscamos reunir pistas antes de decidir."),
                $this->scenario(2, 'La pelota junto al portón', 'Hay una pelota en la acera frente a un portón abierto. No se ve a ninguna persona.', '¿Qué pista conviene anticipar?', [
                    ['id' => 'ignorar', 'label' => 'Nada, porque la pelota está quieta', 'feedback' => 'La pelota puede indicar que una niña o un niño aparecerá de repente.', 'correct' => false],
                    ['id' => 'anticipar', 'label' => 'Alguien podría salir tras la pelota', 'feedback' => 'Correcto. Detectaste una pista y anticipaste una exposición posible.', 'correct' => true],
                    ['id' => 'mover', 'label' => 'Entrar a la calle para moverla', 'feedback' => 'Eso te expondría al tránsito. Primero se protege a las personas.', 'correct' => false],
                ]),
                $this->scenario(3, 'Marcas oscuras en la vía', 'Antes de llegar a un cruce, Luna observa una mancha brillante sobre el pavimento y una motocicleta que se aproxima.', '¿Cómo debe interpretar la escena?', [
                    ['id' => 'solo-mancha', 'label' => 'La mancha es el peligro y no importa quién se acerque', 'feedback' => 'Detectaste un peligro, pero falta considerar quién podría exponerse y qué consecuencia tendría.', 'correct' => false],
                    ['id' => 'relacionar', 'label' => 'La superficie puede reducir el agarre y afectar la trayectoria de la motocicleta', 'feedback' => 'Correcto. Relacionás una pista, una persona expuesta y una consecuencia posible.', 'correct' => true],
                    ['id' => 'probar', 'label' => 'Pisar la mancha para comprobar si resbala', 'feedback' => 'No hace falta exponerse para confirmar un peligro. Se conserva distancia y se advierte por un medio seguro.', 'correct' => false],
                ]),
                $this->text(4, 'Barrido de seis preguntas', "Antes de moverte preguntá: ¿qué veo?, ¿qué no puedo ver?, ¿qué escucho?, ¿quién podría aparecer?, ¿qué puede cambiar?, ¿dónde está mi espacio protegido?\n\nPracticá desde un lugar seguro; no fotografiés placas, rostros ni direcciones."),
            ]),
            $this->riskLesson('RIESGO-OCULTO', 'Reto 2: Lo que no se ve', 2, 15, 'dilemma', 'Reconocer puntos ciegos y obstáculos que esconden movimiento.', $competencyId, ['RIESGO.DETECTA', 'RIESGO.ANTICIPA'], [
                $this->text(1, 'La ausencia de evidencia no es evidencia de ausencia', 'Vehículos estacionados, autobuses, vegetación, curvas y pendientes pueden ocultar personas o vehículos. Si no podés ver el espacio del que vendría el peligro, **reducí la exposición y buscá otro punto de observación**.'),
                $this->scenario(2, 'Camino rural con curva', 'En un camino rural sin acera, una curva y la vegetación impiden ver vehículos que se acercan.', '¿Qué opción crea mejor información?', [
                    ['id' => 'asomarse', 'label' => 'Asomarse desde la calzada', 'feedback' => 'Obtener información no debe obligarte a entrar en la trayectoria del peligro.', 'correct' => false],
                    ['id' => 'protegido', 'label' => 'Permanecer fuera de la calzada y buscar un punto más visible', 'feedback' => 'Correcto. Mejorás la visión manteniendo un espacio protegido.', 'correct' => true],
                    ['id' => 'silencio', 'label' => 'Avanzar si no se escucha motor', 'feedback' => 'El viento, la lluvia o un vehículo silencioso pueden engañar al oído.', 'correct' => false],
                ]),
                $this->scenario(3, 'Entre vehículos estacionados', 'Dos vehículos altos estacionados ocultan parte de la calle. Luna escucha movimiento, pero desde su posición no puede identificar de dónde viene.', '¿Qué principio debe aplicar?', [
                    ['id' => 'salir', 'label' => 'Avanzar entre los vehículos hasta poder ver', 'feedback' => 'Al ganar visión desde la calzada, Luna ya estaría expuesta a aquello que intenta detectar.', 'correct' => false],
                    ['id' => 'otro-cruce', 'label' => 'Retroceder al espacio protegido y elegir un lugar con campo visual abierto', 'feedback' => 'Correcto. Si un punto obliga a exponerse para observar, se busca otro lugar.', 'correct' => true],
                    ['id' => 'oido', 'label' => 'Cruzar si el sonido parece lejano', 'feedback' => 'El sonido puede reflejarse o quedar oculto; no compensa la falta de visión.', 'correct' => false],
                ]),
                $this->text(4, 'Práctica con una maqueta', 'Colocá objetos que representen una calle, una curva y un autobús. Mové una figura detrás de cada obstáculo y comprobá cuándo desaparece de la vista. Explicá cómo cambiarías tu posición sin acercarte al peligro.'),
            ]),
            $this->riskLesson('RIESGO-PREDICE', 'Reto 3: ¿Qué podría pasar después?', 1, 15, 'story', 'Anticipar movimientos probables de distintos actores viales.', $competencyId, ['RIESGO.ANTICIPA'], [
                $this->text(1, 'Anticipar no es adivinar', 'Anticipar significa reconocer posibilidades: un autobús puede arrancar, una puerta puede abrirse, un vehículo puede girar y una persona puede cambiar de dirección. Usá señales, ruedas, luces, postura y espacio disponible, pero conservá margen por si tu predicción falla.'),
                $this->scenario(2, 'Fila frente a la escuela', 'Un vehículo está detenido y una persona menor se mueve en el asiento trasero junto a la puerta que da hacia la calle.', '¿Qué posibilidad debe entrar en tu plan?', [
                    ['id' => 'puerta', 'label' => 'La puerta podría abrirse', 'feedback' => 'Correcto. Reconocés una señal previa y dejás espacio para reaccionar.', 'correct' => true],
                    ['id' => 'ninguna', 'label' => 'Ninguna, porque el vehículo está detenido', 'feedback' => 'Un vehículo detenido también puede generar movimientos inesperados.', 'correct' => false],
                    ['id' => 'pasar', 'label' => 'Pasar muy cerca para hacerlo rápido', 'feedback' => 'Pasar cerca elimina el espacio para reaccionar si la puerta se abre.', 'correct' => false],
                ]),
                $this->scenario(3, 'Ruedas giradas en la esquina', 'Un automóvil está detenido sin direccional, pero sus ruedas apuntan hacia la esquina por donde Luna quiere cruzar.', '¿Qué anticipación es razonable?', [
                    ['id' => 'quieto', 'label' => 'No se moverá porque está detenido', 'feedback' => 'Estar detenido describe el presente, no garantiza el movimiento siguiente.', 'correct' => false],
                    ['id' => 'giro', 'label' => 'Podría girar; Luna debe conservar margen y confirmar su intención', 'feedback' => 'Correcto. Las ruedas son una pista, aunque todavía se debe comprobar antes de actuar.', 'correct' => true],
                    ['id' => 'seguro', 'label' => 'Las ruedas confirman que el automóvil le dará paso', 'feedback' => 'La dirección de las ruedas sugiere una trayectoria, no confirma que la persona conductora haya visto a Luna.', 'correct' => false],
                ]),
                $this->text(4, 'Pausa de tres segundos', 'En una escena segura, nombrá tres cosas que podrían ocurrir en los próximos tres segundos. Después separalas en: probable, posible y poco probable. Todas pueden requerir una salida segura.'),
            ]),
            $this->riskLesson('RIESGO-CLIMA', 'Reto 4: Cuando cambia el entorno', 2, 15, 'web_simulation', 'Replantear la decisión ante lluvia, oscuridad, ruido o tránsito intenso.', $competencyId, ['RIESGO.ANTICIPA', 'RIESGO.REPLANIFICA'], [
                $this->text(1, 'La misma ruta puede tener otro riesgo', 'Un aguacero, la salida de clases, una avería o la noche pueden transformar un lugar conocido. Con lluvia hay menos visibilidad y adherencia; con ruido se ocultan señales auditivas. La respuesta no es hacer lo mismo más rápido: es **volver a evaluar**.'),
                $this->scenario(2, 'Aguacero al salir', 'Comienza un aguacero fuerte. Hay agua acumulada, poca visibilidad y personas corriendo hacia el autobús.', '¿Qué decisión demuestra adaptación?', [
                    ['id' => 'rutina', 'label' => 'Seguir igual porque conocés la ruta', 'feedback' => 'Conocer el sitio no neutraliza las condiciones nuevas.', 'correct' => false],
                    ['id' => 'reevaluar', 'label' => 'Esperar protegido y reevaluar ruta, visibilidad y acompañamiento', 'feedback' => 'Correcto. Cambiás el plan cuando cambia el entorno.', 'correct' => true],
                    ['id' => 'correr', 'label' => 'Correr para reducir el tiempo afuera', 'feedback' => 'La prisa aumenta distracción y riesgo de caída.', 'correct' => false],
                ]),
                $this->scenario(3, 'Apagón en el barrio', 'Al anochecer ocurre un apagón. El semáforo no funciona, hay poca iluminación y varias personas conductoras intentan pasar la intersección.', '¿Qué cambio de plan es más seguro?', [
                    ['id' => 'costumbre', 'label' => 'Cruzar por el lugar habitual siguiendo a otras personas', 'feedback' => 'La costumbre y el grupo no restablecen las señales ni la visibilidad.', 'correct' => false],
                    ['id' => 'alternativa', 'label' => 'Esperar protegido y buscar una ruta iluminada o acompañamiento seguro', 'feedback' => 'Correcto. El fallo de infraestructura convierte una ruta conocida en una situación que debe reevaluarse.', 'correct' => true],
                    ['id' => 'luz-telefono', 'label' => 'Usar la luz del teléfono y cruzar con rapidez', 'feedback' => 'Una luz pequeña no controla los movimientos de la intersección y el teléfono puede dividir la atención.', 'correct' => false],
                ]),
                $this->text(4, 'Semáforo personal', 'Clasificá la situación: **verde**, puedo continuar con atención; **amarillo**, necesito más información o margen; **rojo**, debo detenerme y cambiar el plan. Justificá el color usando pistas concretas.'),
            ]),
            $this->riskLesson('RIESGO-MARGEN', 'Reto 5: Tiempo, espacio y salida', 1, 16, 'dilemma', 'Elegir alternativas que toleren errores y cambios inesperados.', $competencyId, ['RIESGO.MARGEN'], [
                $this->text(1, 'Una decisión segura no depende de que todo salga perfecto', 'El **margen de seguridad** es el tiempo y espacio disponibles para reaccionar. Si una opción exige correr, pasar rozando o confiar en que otra persona no se equivocará, el margen es pequeño.'),
                $this->scenario(2, 'Bus que ya llegó', 'El autobús está en la parada al otro lado. Para alcanzarlo tendrías que cruzar corriendo entre vehículos.', '¿Cuál opción protege mejor la vida?', [
                    ['id' => 'alcanzar', 'label' => 'Correr porque perder el bus sería un problema', 'feedback' => 'Una consecuencia incómoda no justifica eliminar el margen de seguridad.', 'correct' => false],
                    ['id' => 'siguiente', 'label' => 'Usar el cruce seguro aunque se vaya el autobús', 'feedback' => 'Correcto. Priorizás una decisión recuperable y con margen.', 'correct' => true],
                    ['id' => 'senal', 'label' => 'Pedir a alguien que detenga el tránsito', 'feedback' => 'Una señal informal no controla todos los carriles ni movimientos.', 'correct' => false],
                ]),
                $this->scenario(3, 'Espacio que desaparece', 'Luna piensa cruzar por un espacio entre vehículos, pero uno empieza a retroceder lentamente y reduce la zona disponible.', '¿Qué indica que ya no existe margen suficiente?', [
                    ['id' => 'todavia', 'label' => 'Todavía cabe si pasa de lado y rápido', 'feedback' => 'Una opción que exige pasar rozando y rápido ya perdió su margen de seguridad.', 'correct' => false],
                    ['id' => 'detener-plan', 'label' => 'Debe abandonar ese plan y volver a un espacio protegido', 'feedback' => 'Correcto. Cuando el espacio de reacción disminuye, se elige una alternativa recuperable.', 'correct' => true],
                    ['id' => 'confiar', 'label' => 'Puede seguir porque quien conduce seguramente la verá', 'feedback' => 'Una decisión segura no depende de que otra persona detecte y corrija el peligro a tiempo.', 'correct' => false],
                ]),
                $this->text(4, 'Comparador de opciones', 'Para dos rutas o decisiones, compará: visibilidad, espacio protegido, cantidad de movimientos, tiempo para reaccionar y alternativa de salida. Elegí la que siga siendo segura aunque alguien cometa un error.'),
            ]),
            $this->riskLesson('RIESGO-MISION', 'Misión integradora: cambia el plan', 2, 14, 'competency_challenge', 'Integrar detección, anticipación, margen y replanteamiento.', $competencyId, ['RIESGO.DETECTA', 'RIESGO.ANTICIPA', 'RIESGO.MARGEN', 'RIESGO.REPLANIFICA'], [
                $this->text(1, 'El método del detective vial', '**Detectá → anticipá → compará márgenes → decidí → volvé a observar.** Una buena decisión puede cambiar si aparece información nueva.'),
                $this->scenario(2, 'Parada con visibilidad bloqueada', 'Un autobús tapa el cruce, llueve y una motocicleta podría adelantarlo.', '¿Qué integra mejor el método?', [
                    ['id' => 'delante', 'label' => 'Cruzar delante porque el autobús está detenido', 'feedback' => 'El autobús bloquea la información y la motocicleta prevista reduce el margen.', 'correct' => false],
                    ['id' => 'esperar', 'label' => 'Esperar protegido hasta recuperar visibilidad y reevaluar', 'feedback' => 'Correcto. Detectás, anticipás y no actuás sin margen.', 'correct' => true],
                    ['id' => 'escuchar', 'label' => 'Cruzar si no escuchás la motocicleta', 'feedback' => 'La lluvia y el autobús pueden ocultar sonido y movimiento.', 'correct' => false],
                ]),
                $this->scenario(3, 'Ruta accesible interrumpida', 'La rampa está bloqueada por una obra y la alternativa inmediata obliga a bajar a la calzada.', '¿Qué decisión es segura e inclusiva?', [
                    ['id' => 'bajar', 'label' => 'Bajar con cuidado a la calzada', 'feedback' => 'El cuidado no crea protección física ni margen suficiente.', 'correct' => false],
                    ['id' => 'ruta', 'label' => 'Regresar al espacio protegido y buscar otra ruta o apoyo', 'feedback' => 'Correcto. Replanteás el recorrido sin excluir a quien necesita accesibilidad.', 'correct' => true],
                    ['id' => 'solo', 'label' => 'Pasar y dejar que cada persona resuelva', 'feedback' => 'La educación vial también implica convivencia y cuidado colectivo.', 'correct' => false],
                ]),
                $this->scenario(4, 'Cambio inesperado', 'La señal favorece el cruce, pero un vehículo de emergencia se aproxima y otras personas empiezan a avanzar.', '¿Qué criterio debe prevalecer?', [
                    ['id' => 'grupo', 'label' => 'Seguir al grupo', 'feedback' => 'Las acciones de un grupo no reemplazan tu evaluación.', 'correct' => false],
                    ['id' => 'detener', 'label' => 'Permanecer protegido y reevaluar cuando pase', 'feedback' => 'Correcto. La información nueva obliga a detener el plan inicial.', 'correct' => true],
                    ['id' => 'senal', 'label' => 'Avanzar porque la señal da permiso', 'feedback' => 'Una señal no elimina un peligro excepcional presente.', 'correct' => false],
                ]),
                $this->text(5, 'Transferencia a tu vida', 'Elegí un recorrido cotidiano sin registrar datos privados. Explicá: dos peligros, un movimiento posible, el margen que necesitás y la señal que te haría cambiar de plan. Luego realizá una práctica acompañada adecuada para tu edad.'),
            ]),
        ];
    }

    /** @param list<string> $indicators @param list<ContentBlockInput> $blocks */
    private function riskLesson(string $code, string $title, int $position, int $minutes, string $experience, string $objective, string $competencyId, array $indicators, array $blocks): LessonInput
    {
        return $this->lesson($code, $title, $position, $minutes, $experience, $objective, $competencyId, $indicators, $blocks, null, 'RIESGO.ANTICIPA');
    }

    private function stableId(string $suffix): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_DNS, 'edudrive.'.self::COURSE_CODE.'.'.$suffix)->toString();
    }

    /**
     * @param  array<string, string>  $preservedIds
     * @return list<LessonInput>
     */
    private function lessons(string $competencyId, array $preservedIds = []): array
    {
        return [
            $this->lesson('RETO-LUGAR', 'Reto 1: ¿Por dónde cruzamos?', 1, 15, 'visual_exploration', 'Elegir un lugar de cruce señalizado y visible.', $competencyId, ['PEATON.LUGAR'], [
                $this->text(1, 'La historia de Luna', "Luna va al parque con su abuelo. Al frente está la entrada, pero entre ellos y el parque hay una calle. Luna descubre que **el camino más corto no siempre es el más seguro**.\n\nEn Costa Rica, las personas peatones deben usar la acera y cruzar en las esquinas, zonas marcadas o pasos peatonales. En cualquier país, busca siempre el lugar previsto para cruzar y sigue las señales."),
                $this->scenario(2, 'El atajo entre automóviles', 'Luna ve el parque al otro lado. Puede cruzar entre dos automóviles estacionados o caminar unos pasos hasta el cruce peatonal.', '¿Qué decisión protege mejor a Luna?', [
                    ['id' => 'atajo', 'label' => 'Cruzar entre los automóviles', 'feedback' => 'Los automóviles estacionados esconden a Luna de quienes conducen y también le quitan visibilidad.', 'correct' => false],
                    ['id' => 'paso', 'label' => 'Caminar hasta el paso peatonal', 'feedback' => '¡Buena decisión! Allí Luna es más visible y cruza por un espacio previsto para peatones.', 'correct' => true],
                    ['id' => 'correr', 'label' => 'Correr por el punto más corto', 'feedback' => 'Correr reduce el tiempo para observar y reaccionar. Primero hay que elegir un lugar seguro.', 'correct' => false],
                ]),
                $this->scenario(3, 'La esquina con poca visibilidad', 'El paso marcado está en una esquina, pero un vehículo grande estacionado impide ver el tránsito y también puede ocultarte.', '¿Qué hacés antes de decidir cruzar?', [
                    ['id' => 'usar-igual', 'label' => 'Cruzar ahí porque las líneas siempre garantizan seguridad', 'feedback' => 'Las marcas orientan, pero un obstáculo puede impedir que otras personas te vean. La seguridad también exige visibilidad.', 'correct' => false],
                    ['id' => 'buscar-visible', 'label' => 'Permanecer en la acera y buscar un punto permitido con mejor visibilidad', 'feedback' => 'Correcto. No abandonás las reglas: elegís una opción permitida donde puedas observar y ser visto.', 'correct' => true],
                    ['id' => 'asomarse-calzada', 'label' => 'Bajar a la calzada para mirar alrededor del vehículo', 'feedback' => 'Al bajar dejás el espacio protegido antes de tener información suficiente. Primero buscá otra posición segura.', 'correct' => false],
                ]),
                $this->text(4, 'Tu radar de lugares seguros', "Antes de cruzar, busca estas pistas:\n\n- Acera o espacio seguro para esperar.\n- Esquina, paso marcado, semáforo peatonal o puente disponible.\n- Visibilidad en ambas direcciones.\n- Ausencia de obstáculos que te oculten.\n\n**Práctica acompañada:** desde un lugar protegido, señala con una persona adulta dos sitios seguros y uno que no elegirías. No hace falta cruzar la calle."),
            ], $preservedIds['RETO-LUGAR'] ?? null),
            $this->lesson('RETO-OBSERVA', 'Reto 2: Pausa de detective', 1, 15, 'dilemma', 'Detenerse, mirar y escuchar antes de cruzar.', $competencyId, ['PEATON.PAUSA', 'PEATON.OBSERVA'], [
                $this->text(1, 'Detenerse cambia la historia', "Llegar al paso peatonal no significa cruzar de inmediato. Quédate en la acera, **detente, mira, escucha y vuelve a comprobar**. Un vehículo puede acercarse en silencio, doblar o no haberte visto.\n\nAunque tengas prioridad o la señal sea favorable, confirma que las personas conductoras se detuvieron antes de avanzar."),
                $this->scenario(2, 'La señal cambió', 'La señal peatonal permite el paso, pero una motocicleta se aproxima a la esquina y parece que va a doblar.', '¿Qué debe hacer Luna?', [
                    ['id' => 'salir', 'label' => 'Cruzar porque la señal lo permite', 'feedback' => 'La señal ayuda, pero no sustituye la observación. Luna todavía debe confirmar que la motocicleta se detuvo.', 'correct' => false],
                    ['id' => 'confirmar', 'label' => 'Esperar y confirmar que se detenga', 'feedback' => '¡Exacto! Luna usa la señal y también verifica el peligro real antes de avanzar.', 'correct' => true],
                    ['id' => 'telefono', 'label' => 'Mirar el teléfono mientras espera', 'feedback' => 'Las distracciones impiden escuchar y observar cambios importantes en la vía.', 'correct' => false],
                ]),
                $this->scenario(3, 'Un carril se detuvo', 'Un automóvil se detiene frente al paso, pero detrás queda oculto otro carril por donde podría aparecer una motocicleta o bicicleta.', '¿Qué comprobación falta?', [
                    ['id' => 'agradecer-cruzar', 'label' => 'Agradecer y cruzar de inmediato', 'feedback' => 'El primer vehículo está controlado, pero todavía no tenés información sobre el carril oculto.', 'correct' => false],
                    ['id' => 'todos-movimientos', 'label' => 'Confirmar que todos los carriles y giros estén controlados', 'feedback' => 'Correcto. Comprobás cada trayectoria posible antes de salir del espacio protegido.', 'correct' => true],
                    ['id' => 'seguir-persona', 'label' => 'Seguir a quien empiece a cruzar primero', 'feedback' => 'Otra persona puede no haber visto el peligro. Tu decisión necesita su propia comprobación.', 'correct' => false],
                ]),
                $this->text(4, 'Ensayo sin calle', "Haz este ensayo en casa con una línea imaginaria:\n\n1. Detente antes de la línea.\n2. Guarda cualquier pantalla o juguete.\n3. Mira y escucha hacia todos los lugares de donde podría venir un vehículo.\n4. Di en voz alta: “veo, escucho y confirmo”.\n5. Avanza solamente cuando tu acompañante diga que el entorno está seguro."),
            ], $preservedIds['RETO-OBSERVA'] ?? null),
            $this->lesson('RETO-INTEGRA', 'Reto final: completa la misión', 1, 19, 'competency_challenge', 'Aplicar la secuencia completa de cruce seguro.', $competencyId, ['PEATON.LUGAR', 'PEATON.PAUSA', 'PEATON.OBSERVA', 'PEATON.CRUZA'], [
                $this->text(1, 'La secuencia que llevas contigo', "Un cruce seguro no depende de memorizar una frase: depende de tomar buenas decisiones.\n\n**Elige → detente → observa y escucha → confirma → cruza con calma.**\n\nContinúa atento mientras cruzas. Ve directamente al otro lado, sin correr, jugar ni regresar de repente."),
                $this->scenario(2, 'El reto del balón', 'Luna ya empezó a cruzar con su abuelo. Su balón cae y rueda hacia atrás.', '¿Qué acción completa la misión de forma segura?', [
                    ['id' => 'volver', 'label' => 'Regresar corriendo por el balón', 'feedback' => 'Cambiar de dirección de repente sorprende a quienes conducen. Ningún objeto vale más que la seguridad.', 'correct' => false],
                    ['id' => 'seguir', 'label' => 'Seguir hasta la acera y pedir ayuda', 'feedback' => '¡Misión cumplida! Luna termina el cruce, permanece visible y pide ayuda desde un lugar protegido.', 'correct' => true],
                    ['id' => 'detener', 'label' => 'Quedarse quieta en medio de la calle', 'feedback' => 'Detenerse en la calzada prolonga la exposición al peligro. Debe completar el cruce con calma.', 'correct' => false],
                ]),
                $this->scenario(3, 'La señal cambia durante el cruce', 'Luna ya está cruzando por un paso sin isla central. La señal comienza a parpadear cuando ella va a mitad del recorrido.', '¿Qué conducta es más predecible y segura?', [
                    ['id' => 'regresar', 'label' => 'Regresar rápidamente al punto de inicio', 'feedback' => 'Cambiar de dirección de repente puede sorprender a quienes ya esperan tu trayectoria.', 'correct' => false],
                    ['id' => 'continuar-calma', 'label' => 'Continuar directamente y con calma hasta la acera', 'feedback' => 'Correcto. Terminás el recorrido sin correr, detenerte ni hacer movimientos inesperados.', 'correct' => true],
                    ['id' => 'correr', 'label' => 'Correr para ganarle al cambio de señal', 'feedback' => 'Correr aumenta el riesgo de caída y reduce tu capacidad de observar mientras completás el cruce.', 'correct' => false],
                ]),
                $this->text(4, 'Prueba de transferencia', "Con una persona adulta, observa un recorrido cotidiano y conversa:\n\n- ¿Dónde esperarías antes de cruzar?\n- ¿Qué podría dificultar que te vean?\n- ¿De dónde podrían venir vehículos, bicicletas o motocicletas?\n- ¿Qué harías si una señal y la situación real parecen contradecirse?\n\n**Compromiso vial:** “Me detengo y compruebo; no cruzo por impulso”. Al completar esta lección, el aprendizaje queda listo para aportar evidencia al Pasaporte Vial."),
            ], $preservedIds['RETO-INTEGRA'] ?? null),
        ];
    }

    /** @return list<LessonInput> */
    private function supplementalLessons(string $competencyId): array
    {
        return [
            $this->lesson('RETO-ACTORES', 'Reto 2: Todos compartimos la vía', 2, 11, 'story', 'Reconocer actores viales y anticipar sus movimientos.', $competencyId, ['PEATON.LUGAR'], [
                $this->text(1, 'Una vía, muchas personas', "En una misma calle conviven **peatones, ciclistas, motociclistas, pasajeros y conductores**. También participan personas que usan silla de ruedas, bastón, coche infantil o animales de asistencia. Cada quien ve, escucha y se mueve de manera diferente.\n\nLa convivencia vial comienza al preguntarnos: ¿me pueden ver?, ¿puedo verlos?, ¿qué podrían hacer después?"),
                $this->scenario(2, 'El autobús en la parada', 'Un autobús se detiene y varias personas bajan. Luna necesita llegar al otro lado de la calle.', '¿Cuál es la decisión más segura?', [
                    ['id' => 'frente', 'label' => 'Cruzar inmediatamente por delante del autobús', 'feedback' => 'El autobús oculta a Luna y también puede impedirle ver vehículos que se aproximan.', 'correct' => false],
                    ['id' => 'esperar', 'label' => 'Esperar en la acera y buscar un cruce visible', 'feedback' => 'Correcto. Esperar permite recuperar visibilidad y elegir un lugar preparado para cruzar.', 'correct' => true],
                    ['id' => 'detras', 'label' => 'Cruzar corriendo por detrás del autobús', 'feedback' => 'Detrás del autobús también hay puntos ciegos y puede aparecer otro vehículo.', 'correct' => false],
                ]),
                $this->scenario(3, 'Movimientos que se cruzan', 'Luna espera en la acera. Un automóvil sale lentamente de un garaje y una bicicleta se acerca por el borde de la vía casi sin hacer ruido.', '¿Qué debe hacer antes de continuar?', [
                    ['id' => 'solo-carro', 'label' => 'Esperar únicamente a que pase el automóvil', 'feedback' => 'El automóvil no es el único actor. La bicicleta también puede cruzar su trayectoria.', 'correct' => false],
                    ['id' => 'revisar-todos', 'label' => 'Permanecer protegida y volver a comprobar ambas trayectorias', 'feedback' => 'Correcto. Reconocer a todos los actores y anticipar sus movimientos evita decidir con información incompleta.', 'correct' => true],
                    ['id' => 'avisar', 'label' => 'Entrar a la vía y hacer señas para que ambos se detengan', 'feedback' => 'Hacerse visible ayuda, pero no justifica abandonar el espacio protegido cuando todavía hay trayectorias activas.', 'correct' => false],
                ]),
                $this->text(4, 'Práctica de empatía vial', "Desde un espacio protegido, identifica tres actores viales. Para cada uno responde: **¿qué puede ver?, ¿qué puede ocultarle la vista?, ¿qué movimiento podría hacer?**\n\nPara niñas y niños, esta actividad siempre se realiza con una persona adulta y sin entrar a la calzada."),
            ]),
            $this->lesson('RETO-CONDICIONES', 'Reto 3: Cuando el entorno cambia', 2, 11, 'dilemma', 'Adaptar la observación ante lluvia, oscuridad y distracciones.', $competencyId, ['PEATON.PAUSA', 'PEATON.OBSERVA'], [
                $this->text(1, 'Ver y ser visto', "La lluvia, la neblina, la oscuridad, el ruido y los reflejos cambian lo que las personas pueden percibir. En Costa Rica, un aguacero puede reducir la visibilidad en pocos minutos. La regla universal es sencilla: **si las condiciones empeoran, aumentamos la precaución**.\n\nGuardá el teléfono, quitá los audífonos al acercarte al cruce y buscá contacto visual sin asumir que ya te vieron."),
                $this->scenario(2, 'Salida bajo la lluvia', 'Llueve fuerte. El paso peatonal está cerca, pero el agua y una sombrilla dificultan ver hacia un lado.', '¿Qué acción reduce mejor el riesgo?', [
                    ['id' => 'rapido', 'label' => 'Cruzar rápido antes de mojarse más', 'feedback' => 'La prisa reduce el tiempo para percibir peligros y aumenta la posibilidad de resbalar.', 'correct' => false],
                    ['id' => 'ajustar', 'label' => 'Esperar, acomodar la sombrilla y comprobar nuevamente', 'feedback' => 'Bien. Primero recuperás visibilidad y equilibrio; luego verificás que sea seguro.', 'correct' => true],
                    ['id' => 'seguir', 'label' => 'Seguir a otra persona sin observar', 'feedback' => 'Cada persona debe comprobar el entorno; seguir a alguien no garantiza que la situación siga siendo segura.', 'correct' => false],
                ]),
                $this->scenario(3, 'Regreso al anochecer', 'Está oscureciendo y Luna usa ropa de color oscuro. En una esquina sin buena iluminación no logra confirmar si una persona conductora la ha visto.', '¿Cuál es la mejor respuesta?', [
                    ['id' => 'derecho', 'label' => 'Cruzar porque las personas peatones tienen derecho de paso', 'feedback' => 'El derecho de paso no sustituye la visibilidad ni elimina el riesgo de que no la hayan visto.', 'correct' => false],
                    ['id' => 'visible', 'label' => 'Buscar un cruce iluminado, hacerse visible y confirmar antes de avanzar', 'feedback' => 'Correcto. Cuando baja la visibilidad, se elige un lugar más claro y se aumenta la comprobación.', 'correct' => true],
                    ['id' => 'telefono', 'label' => 'Encender la pantalla del teléfono mientras cruza', 'feedback' => 'La pantalla puede distraer y no garantiza que otras personas la detecten a tiempo.', 'correct' => false],
                ]),
                $this->text(4, 'Laboratorio de sentidos', "En casa, compará cómo cambia tu atención al sostener un objeto, escuchar música o mirar una pantalla mientras seguís instrucciones sencillas. **No hagás esta prueba cerca de una calle.**\n\nConversá: ¿qué distracción te hizo perder información?, ¿qué hábito usarás antes de cruzar?"),
            ]),
            $this->lesson('RETO-IMPREVISTOS', 'Reto experto: planes que pueden cambiar', 2, 13, 'competency_challenge', 'Responder con seguridad cuando aparece un imprevisto durante el cruce.', $competencyId, ['PEATON.LUGAR', 'PEATON.PAUSA', 'PEATON.OBSERVA', 'PEATON.CRUZA'], [
                $this->text(1, 'Una decisión se vuelve a comprobar', "La vía cambia mientras nos movemos. Un vehículo puede doblar, una bicicleta puede acercarse o la señal puede cambiar. Una persona segura no actúa en automático: **mantiene la atención y ajusta su decisión sin movimientos repentinos**.\n\nSi todavía estás en la acera, esperá. Si ya cruzás, mantené una trayectoria predecible y buscá el lugar protegido más cercano."),
                $this->scenario(2, 'Vehículo que gira', 'Luna tiene señal peatonal favorable y ya comprobó el entorno. Antes de avanzar, nota que un automóvil va a girar hacia el cruce.', '¿Qué demuestra mejor el aprendizaje?', [
                    ['id' => 'prioridad', 'label' => 'Avanzar porque tiene prioridad', 'feedback' => 'Tener prioridad no elimina el peligro. La vida está antes que demostrar quién tiene la razón.', 'correct' => false],
                    ['id' => 'pausa', 'label' => 'Mantenerse en la acera y confirmar que se detenga', 'feedback' => 'Correcto. Luna actualiza su decisión con la nueva información sin exponerse.', 'correct' => true],
                    ['id' => 'sorpresa', 'label' => 'Entrar y luego devolverse rápidamente', 'feedback' => 'Los movimientos repentinos son difíciles de anticipar. Es mejor no iniciar el cruce mientras exista duda.', 'correct' => false],
                ]),
                $this->scenario(3, 'Una sirena cambia el plan', 'La señal peatonal se enciende, pero Luna todavía está en la acera y escucha una ambulancia que se aproxima a la intersección.', '¿Cómo debe responder?', [
                    ['id' => 'senal', 'label' => 'Cruzar de inmediato porque la señal está a su favor', 'feedback' => 'Una señal favorable no vuelve estático el entorno. La emergencia acaba de cambiar la situación.', 'correct' => false],
                    ['id' => 'protegida', 'label' => 'Seguir en la acera, ubicar la ambulancia y volver a comprobar después', 'feedback' => 'Correcto. Ante información nueva, Luna conserva su espacio protegido y toma una decisión nueva.', 'correct' => true],
                    ['id' => 'correr', 'label' => 'Correr para terminar antes de que llegue la ambulancia', 'feedback' => 'Competir contra el tiempo reduce el margen de seguridad y puede sorprender a quienes atienden la emergencia.', 'correct' => false],
                ]),
                $this->text(4, 'Misión en tu comunidad', "Con acompañamiento apropiado, elegí un cruce cotidiano y construí un plan:\n\n1. Lugar protegido para esperar.\n2. Actores que podrían aparecer.\n3. Obstáculos y condiciones del entorno.\n4. Señal para decidir que todavía no es seguro.\n5. Acción segura ante un cambio inesperado.\n\nExplicá el plan con tus propias palabras. Esa explicación prepara la práctica observada y la reflexión del Pasaporte Vial."),
            ]),
        ];
    }

    /** @return list<LessonInput> */
    private function advancedLessons(string $competencyId): array
    {
        return [
            $this->lesson('RETO-RUTA-INCLUSIVA', 'Reto 3: Una ruta segura para todas las personas', 3, 12, 'community_observation', 'Evaluar accesibilidad, visibilidad y protección al elegir una ruta.', $competencyId, ['PEATON.LUGAR'], [
                $this->text(1, 'La mejor ruta no siempre es la más corta', "Una ruta segura debe considerar a todas las personas. Una acera estrecha, un vehículo sobre la acera, una rampa bloqueada o un hueco pueden obligar a alguien a acercarse a la calzada. Esto afecta especialmente a niñas y niños, personas mayores y quienes usan silla de ruedas, bastón o coche infantil.\n\nElegir bien significa comparar **visibilidad, accesibilidad, iluminación, velocidad del tránsito y lugares protegidos para esperar**."),
                $this->scenario(2, 'Dos caminos a la escuela', 'La ruta corta tiene la acera bloqueada y obliga a caminar cerca de los vehículos. La otra ruta tarda unos minutos más, pero tiene acera continua y cruce marcado.', '¿Cuál opción demuestra ciudadanía vial?', [
                    ['id' => 'corta', 'label' => 'Usar la ruta corta porque siempre se ha usado', 'feedback' => 'La costumbre no elimina los obstáculos. Una ruta debe reevaluarse cuando cambian sus condiciones.', 'correct' => false],
                    ['id' => 'protegida', 'label' => 'Elegir la ruta con acera continua y cruce marcado', 'feedback' => 'Correcto. Unos minutos adicionales pueden reducir la exposición al riesgo y facilitar el recorrido para todas las personas.', 'correct' => true],
                    ['id' => 'calzada', 'label' => 'Bajar a la calzada para rodear el obstáculo', 'feedback' => 'Entrar a la zona vehicular aumenta la exposición. Es preferible buscar una alternativa protegida.', 'correct' => false],
                ]),
                $this->scenario(3, 'La rampa está bloqueada', 'Una macetera bloquea la rampa del cruce. Cerca espera una persona que usa silla de ruedas y la calzada tiene tránsito.', '¿Qué acción respeta autonomía y seguridad?', [
                    ['id' => 'empujar', 'label' => 'Empujar la silla sin preguntar para pasar rápido', 'feedback' => 'Ayudar sin consentimiento puede ser inseguro y no respeta la autonomía de la persona.', 'correct' => false],
                    ['id' => 'preguntar', 'label' => 'Preguntar si necesita apoyo y buscar juntos una alternativa protegida', 'feedback' => 'Correcto. Se ofrece apoyo con consentimiento y se evita convertir la calzada en una solución improvisada.', 'correct' => true],
                    ['id' => 'calzada', 'label' => 'Indicarle que rodee la macetera por la calzada', 'feedback' => 'La obstrucción no debe resolverse exponiendo a la persona al tránsito. También conviene reportarla por un canal seguro.', 'correct' => false],
                ]),
                $this->text(4, 'Mapa de movilidad segura', "Con una persona adulta, dibujá un recorrido conocido. Marcá en verde los espacios protegidos, en amarillo los puntos que requieren más atención y en rojo los lugares que evitarías.\n\nNo incluyás direcciones personales ni datos privados. El objetivo es aprender a justificar una ruta, no registrar dónde vivís."),
            ]),
            $this->lesson('RETO-DISTANCIA', 'Reto 4: Velocidad, distancia y tiempo', 3, 12, 'web_simulation', 'Comprender que la distancia aparente no garantiza tiempo suficiente para cruzar.', $competencyId, ['PEATON.PAUSA', 'PEATON.OBSERVA'], [
                $this->text(1, 'Nuestro cerebro también puede equivocarse', "Un vehículo lejano puede acercarse más rápido de lo esperado. El tamaño, el ruido y la pendiente alteran nuestra percepción. Los vehículos eléctricos pueden ser más silenciosos, y una motocicleta puede quedar oculta detrás de otro vehículo.\n\nPor eso no calculamos “a ver si alcanza”. Esperamos una oportunidad amplia, usamos el cruce previsto y confirmamos que quienes conducen se detuvieron."),
                $this->scenario(2, 'Parece que está lejos', 'Un automóvil se ve lejos, pero circula rápido. Luna siente que podría alcanzar a cruzar si corre.', '¿Cuál razonamiento es más seguro?', [
                    ['id' => 'calcular', 'label' => 'Intentarlo porque parece haber tiempo', 'feedback' => 'La distancia visual no revela con precisión la velocidad ni el tiempo disponible.', 'correct' => false],
                    ['id' => 'esperar', 'label' => 'Esperar una oportunidad clara sin necesidad de correr', 'feedback' => 'Correcto. Una decisión segura deja margen para errores y cambios inesperados.', 'correct' => true],
                    ['id' => 'competir', 'label' => 'Cruzar si otra persona también corre', 'feedback' => 'La decisión de otra persona no sustituye tu propia comprobación del entorno.', 'correct' => false],
                ]),
                $this->scenario(3, 'Bicicleta en bajada', 'Desde una esquina, una bicicleta eléctrica parece estar lejos y casi no se escucha, pero viene bajando una pendiente.', '¿Qué dato debe guiar la decisión?', [
                    ['id' => 'silencio', 'label' => 'Si no se escucha, debe venir despacio', 'feedback' => 'El poco ruido no permite calcular la velocidad de una bicicleta o vehículo eléctrico.', 'correct' => false],
                    ['id' => 'margen', 'label' => 'La pendiente puede aumentar su velocidad; conviene esperar un margen amplio', 'feedback' => 'Correcto. La decisión considera velocidad posible, distancia y tiempo, sin apostar a alcanzar.', 'correct' => true],
                    ['id' => 'correr', 'label' => 'Cruzar corriendo antes de que llegue', 'feedback' => 'Correr convierte la decisión en una competencia y deja poco margen ante cualquier cambio.', 'correct' => false],
                ]),
                $this->text(4, 'Experimento protegido', "Con objetos de juguete y una línea en el piso, compará un movimiento lento y uno rápido. Observá cómo el mismo espacio puede recorrerse en tiempos diferentes.\n\nConclusión: **no cruzamos compitiendo contra un vehículo**. Buscamos condiciones que permitan avanzar con calma."),
            ]),
            $this->lesson('MISION-INTEGRADORA', 'Misión integradora: explicá tu decisión', 3, 12, 'competency_challenge', 'Justificar y transferir una decisión completa de cruce seguro.', $competencyId, ['PEATON.LUGAR', 'PEATON.PAUSA', 'PEATON.OBSERVA', 'PEATON.CRUZA'], [
                $this->text(1, 'Tu criterio protege la vida', "Llegaste al desafío integrador. No buscamos que repitás una frase: queremos que puedas explicar **qué observaste, qué riesgo identificaste, qué opción descartaste y por qué tu decisión protege la vida**.\n\nRecordá: elegir el lugar, detenerse, observar y escuchar, confirmar, cruzar con calma y seguir atento."),
                $this->scenario(2, 'La salida del centro educativo', 'Ha terminado la jornada. Hay autobuses, motocicletas, familias, lluvia ligera y muchas personas conversando. El cruce marcado está unos metros adelante y una persona conductora te hace una señal con la mano desde un carril.', '¿Qué decisión integra mejor todo lo aprendido?', [
                    ['id' => 'gesto', 'label' => 'Cruzar de inmediato porque una persona dio paso', 'feedback' => 'Un gesto solo informa sobre un vehículo. Todavía puede haber peligro en otros carriles o direcciones.', 'correct' => false],
                    ['id' => 'integrar', 'label' => 'Ir al cruce, detenerse y comprobar todos los movimientos', 'feedback' => 'Excelente. Usás infraestructura, controlás la distracción y verificás el entorno completo antes de avanzar.', 'correct' => true],
                    ['id' => 'grupo', 'label' => 'Seguir al grupo sin comprobar personalmente', 'feedback' => 'Un grupo también puede equivocarse. Cada persona necesita mantener atención y criterio propio.', 'correct' => false],
                ]),
                $this->scenario(3, 'La acera bloqueada', 'En el camino habitual hay una construcción que ocupa toda la acera. Una persona que usa silla de ruedas tampoco puede pasar y la esquina más cercana tiene poca visibilidad.', '¿Qué respuesta demuestra una decisión segura e inclusiva?', [
                    ['id' => 'calzada', 'label' => 'Continuar por la calzada con mucho cuidado', 'feedback' => 'La atención no elimina la exposición a vehículos ni resuelve la barrera para otras personas.', 'correct' => false],
                    ['id' => 'alternativa', 'label' => 'Regresar a un punto protegido y elegir otra ruta accesible', 'feedback' => 'Correcto. Reevaluás el recorrido, evitás la exposición y elegís una alternativa que también considera la accesibilidad.', 'correct' => true],
                    ['id' => 'separarse', 'label' => 'Pasar primero y dejar que cada persona resuelva', 'feedback' => 'La convivencia vial requiere reconocer que la infraestructura puede afectar de forma distinta a otras personas.', 'correct' => false],
                ]),
                $this->scenario(4, 'La señal y el vehículo silencioso', 'La señal peatonal está favorable. Hay oscuridad y un vehículo eléctrico se aproxima lentamente, pero no es claro si la persona conductora te ha visto.', '¿Qué evidencia un criterio vial confiable?', [
                    ['id' => 'senal', 'label' => 'Cruzar porque la señal confirma que corresponde', 'feedback' => 'La señal organiza el tránsito, pero no confirma que todas las personas percibieron la situación.', 'correct' => false],
                    ['id' => 'confirmar', 'label' => 'Esperar en la acera hasta confirmar que el vehículo se detuvo', 'feedback' => 'Correcto. Integrás señalización, visibilidad, silencio del vehículo y comprobación antes de actuar.', 'correct' => true],
                    ['id' => 'sonido', 'label' => 'Cruzar porque no se escucha un motor fuerte', 'feedback' => 'El sonido no es una medida suficiente: algunos vehículos son silenciosos y el ruido del entorno puede ocultarlos.', 'correct' => false],
                ]),
                $this->text(5, 'Cierre y transferencia', "Explicá con tus palabras:\n\n- Qué señales te ayudan, pero no sustituyen la observación.\n- Cómo cambian tus decisiones con lluvia, oscuridad o ruido.\n- Cómo cuidás a personas con necesidades diferentes.\n- Qué harías ante un vehículo que gira o un objeto que cae.\n\nLuego realizá una práctica segura con acompañamiento. La observación y tu reflexión aportarán evidencia distinta a simplemente completar la pantalla."),
            ]),
        ];
    }

    /**
     * @param  list<string>  $indicators
     * @param  list<ContentBlockInput>  $blocks
     */
    private function lesson(string $code, string $title, int $position, int $minutes, string $experience, string $objective, string $competencyId, array $indicators, array $blocks, ?string $id = null, string $subcompetencyCode = 'PEATON.SECUENCIA'): LessonInput
    {
        $id ??= Uuid::uuid5(Uuid::NAMESPACE_DNS, 'edudrive.'.self::COURSE_CODE.'.'.$code)->toString();
        $stage = 'pending_review';

        return new LessonInput($id, $code, $title, $objective, $minutes, $position, $blocks, LessonLearningDesign::fromArray([
            'stage' => $stage,
            'jurisdictions' => ['GLOBAL', 'CR'],
            'experience_type' => $experience,
            'behavior_objective' => $objective,
            'competency_id' => $competencyId,
            'subcompetency_code' => $subcompetencyCode,
            'indicator_codes' => $indicators,
            'evidence_rules' => array_map(static fn (string $indicator): array => ['indicator_code' => $indicator, 'event_type' => 'lesson_completed', 'minimum_observations' => 1, 'weight' => 1], $indicators),
            'requires_guardian' => true,
            'normative_sources' => [
                ['url' => 'https://pgrweb.go.cr/Scij/Busqueda/Normativa/Normas/nrm_texto_completo.aspx?nValor1=1&nValor2=73504&param1=NRM&strTipM=FN', 'reviewed_at' => '2026-09-08'],
                ['url' => 'https://mep.go.cr/programas-proyectos/camino-seguro', 'reviewed_at' => '2026-09-08'],
            ],
            'version' => 2,
        ]));
    }

    private function text(int $position, string $title, string $markdown): ContentBlockInput
    {
        return new ContentBlockInput((string) Str::uuid(), 'text', $position, ['title' => $title, 'markdown' => $markdown]);
    }

    /** @param list<array{id: string, label: string, feedback: string, correct: bool}> $choices */
    private function scenario(int $position, string $title, string $context, string $prompt, array $choices): ContentBlockInput
    {
        return new ContentBlockInput((string) Str::uuid(), 'scenario', $position, [
            'title' => $title,
            'context' => $context,
            'prompt' => $prompt,
            'accessible_text' => $context.' '.$prompt.' Las opciones y su retroalimentación están disponibles como texto y pueden recorrerse con teclado.',
            'choices' => $choices,
        ]);
    }
}
