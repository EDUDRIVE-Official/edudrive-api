<?php

declare(strict_types=1);

namespace Database\Seeders;

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
use Modules\Academic\Domain\ValueObjects\LessonLearningDesign;
use Ramsey\Uuid\Uuid;

final class VisibleCyclingPilotSeeder extends Seeder
{
    private const string COURSE_CODE = 'EDU-EXP-002';

    private const string COMPETENCY_CODE = 'CICLISTA-VISIBLE';

    private const string ROUTE_COMPETENCY_CODE = 'CICLISTA-RUTA-SEGURA';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $existing = DB::table('academic_courses')->where('code', self::COURSE_CODE)->first(['id']);
        if ($existing !== null) {
            $this->upgradeExistingCourse((string) $existing->id, $this->competencyId(), $this->routeCompetencyId());

            return;
        }

        $competencyId = $this->competencyId();
        $routeCompetencyId = $this->routeCompetencyId();
        $course = app(CreateCourseHandler::class)->handle(new CreateCourseCommand(
            code: self::COURSE_CODE,
            title: 'Misión Pedalea Visible',
            description: 'Una experiencia para preparar la bicicleta, hacerse visible y tomar decisiones seguras antes y durante cada recorrido.',
            objectives: 'Revisar la bicicleta y el equipo; aumentar la visibilidad; mantener atención y anticipar riesgos al circular.',
            prerequisites: 'Saber mantener el equilibrio en bicicleta. Las prácticas deben realizarse en un espacio protegido y con acompañamiento cuando corresponda.',
            modality: 'virtual',
            durationHours: 5,
        ));

        $moduleId = (string) Str::uuid();
        $unitIds = [(string) Str::uuid(), (string) Str::uuid(), (string) Str::uuid()];
        $units = [
            new CourseUnitInput($unitIds[0], 'ANTES-SALIR', '1. Bicicleta y cuerpo listos', 'Convierte la revisión previa en una rutina.', 'Comprobar casco, frenos, llantas, piezas y ajuste antes de pedalear.', 44, 1, []),
            new CourseUnitInput($unitIds[1], 'SER-VISIBLE', '2. Ver y ser visible', 'Descubre cómo la luz, el color y la posición comunican tu presencia.', 'Elegir elementos y conductas que aumentan la visibilidad.', 41, 2, [$unitIds[0]]),
            new CourseUnitInput($unitIds[2], 'DECIDIR-RUTA', '3. Atención en movimiento', 'Integra observación, señales y anticipación.', 'Circular sin distracciones y responder con calma ante riesgos.', 51, 3, [$unitIds[1]]),
        ];

        app(ReplaceCourseCurriculumHandler::class)->handle(new ReplaceCourseCurriculumCommand($course->id, [
            new CourseModuleInput($moduleId, 'MISION-BICI', 'Misión: Pedalea Visible', 'Nueve retos para preparar, comunicar y decidir antes de moverse en bicicleta.', 'Completar una rutina de seguridad aplicable en Costa Rica y en cualquier comunidad.', 136, 1, [], $units),
        ]));
        $this->upsertRouteCurriculum($course->id, $moduleId);

        $lessons = $this->lessons($competencyId);
        $supplemental = $this->supplementalLessons($competencyId);
        $advanced = $this->advancedLessons($competencyId);
        foreach ($unitIds as $index => $unitId) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($course->id, $unitId, [$lessons[$index], $supplemental[$index], $advanced[$index]]));
        }
        $this->replaceRouteContent($course->id, $routeCompetencyId);

        app(SubmitCourseForReviewHandler::class)->handle(new SubmitCourseForReviewCommand($course->id));
        app(ApproveCourseHandler::class)->handle(new ApproveCourseCommand($course->id));
        app(PublishCourseHandler::class)->handle(new PublishCourseCommand($course->id));
    }

    private function upgradeExistingCourse(string $courseId, string $competencyId, string $routeCompetencyId): void
    {
        $module = DB::table('academic_course_modules')->where('course_id', $courseId)->orderBy('position')->first();
        if ($module === null) {
            return;
        }
        $units = DB::table('academic_course_units')->where('module_id', $module->id)->orderBy('position')->get();
        if ($units->count() !== 3) {
            return;
        }
        $unitIds = $units->pluck('id')->map(static fn ($id): string => (string) $id)->all();
        /** @var array<string, string> $preservedIds */
        $preservedIds = DB::table('academic_lessons')->whereIn('unit_id', $unitIds)
            ->whereIn('code', ['RETO-REVISION', 'RETO-VISIBLE', 'RETO-ATENCION'])->pluck('id', 'code')
            ->map(static fn ($id): string => (string) $id)->all();

        app(ReopenCourseHandler::class)->handle(new ReopenCourseCommand($courseId));
        DB::table('academic_courses')->where('id', $courseId)->update(['duration_hours' => 5, 'updated_at' => now()]);
        DB::table('academic_course_modules')->where('id', $module->id)->update(['title' => 'Misión: Pedalea Visible', 'description' => 'Nueve retos para preparar, comunicar y decidir antes de moverse en bicicleta.', 'duration_minutes' => 136, 'updated_at' => now()]);
        foreach ([44, 41, 51] as $index => $minutes) {
            DB::table('academic_course_units')->where('id', $unitIds[$index])->update(['duration_minutes' => $minutes, 'updated_at' => now()]);
        }
        $this->upsertRouteCurriculum($courseId, (string) $module->id);

        $lessons = $this->lessons($competencyId, $preservedIds);
        $supplemental = $this->supplementalLessons($competencyId);
        $advanced = $this->advancedLessons($competencyId);
        foreach ($unitIds as $index => $unitId) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($courseId, $unitId, [$lessons[$index], $supplemental[$index], $advanced[$index]]));
        }
        $this->replaceRouteContent($courseId, $routeCompetencyId);
        app(SubmitCourseForReviewHandler::class)->handle(new SubmitCourseForReviewCommand($courseId));
        app(ApproveCourseHandler::class)->handle(new ApproveCourseCommand($courseId));
        app(PublishCourseHandler::class)->handle(new PublishCourseCommand($courseId));
    }

    private function competencyId(): string
    {
        $existing = DB::table('academic_competencies')->where('code', self::COMPETENCY_CODE)->first(['id']);
        if ($existing !== null) {
            return (string) $existing->id;
        }

        $competency = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand(
            self::COMPETENCY_CODE,
            'Se moviliza en bicicleta de manera visible y preventiva',
            'Prepara su equipo, comunica su presencia y anticipa peligros para reducir riesgos propios y compartidos.',
            'vulnerable_road_users',
            'foundation',
        ));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($competency->id, 'CICLISTA.RUTINA', 'Aplica una rutina preventiva de movilidad ciclista'));
        foreach ([
            ['CICLISTA.REVISA', 'Comprueba casco, frenos, llantas y piezas antes del recorrido.'],
            ['CICLISTA.VISIBLE', 'Utiliza elementos de visibilidad apropiados para las condiciones.'],
            ['CICLISTA.ATIENDE', 'Mantiene oído, vista y manos disponibles para conducir.'],
            ['CICLISTA.ANTICIPA', 'Reduce velocidad y elige una respuesta segura ante un peligro.'],
        ] as [$code, $description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($competency->id, 'CICLISTA.RUTINA', $code, $description));
        }

        return $competency->id;
    }

    private function routeCompetencyId(): string
    {
        $existing = DB::table('academic_competencies')->where('code', self::ROUTE_COMPETENCY_CODE)->first(['id']);
        if ($existing !== null) return (string) $existing->id;

        $competency = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand(
            self::ROUTE_COMPETENCY_CODE,
            'Planifica y recorre rutas ciclistas con margen de seguridad',
            'Compara rutas, interpreta intersecciones y adapta velocidad y posición ante conflictos previsibles.',
            'risk_management',
            'foundation',
        ));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($competency->id, 'CICLISTA.RUTA', 'Planifica, observa y adapta su recorrido'));
        foreach ([
            ['CICLISTA.RUTA.ELIGE', 'Compara rutas por velocidad, visibilidad, superficie y complejidad.'],
            ['CICLISTA.RUTA.INTERSECCION', 'Reduce, observa y comunica antes de una intersección.'],
            ['CICLISTA.RUTA.POSICION', 'Evita puntos ciegos y conserva espacio para reaccionar.'],
            ['CICLISTA.RUTA.REPLANIFICA', 'Cambia el plan ante clima, obras o condiciones inesperadas.'],
        ] as [$code, $description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($competency->id, 'CICLISTA.RUTA', $code, $description));
        }

        return $competency->id;
    }

    private function routeModule(string $prerequisiteModuleId): CourseModuleInput
    {
        $moduleId = $this->stableId('MOD-RUTA');
        $unitIds = [$this->stableId('UNI-PLANIFICA'), $this->stableId('UNI-INTERSECCIONES'), $this->stableId('UNI-ADAPTA')];

        return new CourseModuleInput($moduleId, 'MISION-RUTA-BICI', 'Misión 2: Elegí y recorré una ruta segura', 'Seis retos para comparar recorridos y responder a conflictos reales de movilidad ciclista.', 'Planificar una ruta, resolver intersecciones y cambiar el plan cuando desaparece el margen.', 123, 2, [$prerequisiteModuleId], [
            new CourseUnitInput($unitIds[0], 'PLANIFICA-RUTA', '1. La ruta se decide antes', 'Compara protección, velocidad, superficie y complejidad.', 'Justificar una ruta por seguridad y no solamente por distancia.', 38, 1, []),
            new CourseUnitInput($unitIds[1], 'LEE-INTERSECCION', '2. Intersecciones y movimientos', 'Observa giros, cruces y puntos ciegos.', 'Preparar velocidad, posición y comunicación antes del conflicto.', 42, 2, [$unitIds[0]]),
            new CourseUnitInput($unitIds[2], 'ADAPTA-RECORRIDO', '3. La ruta también cambia', 'Responde a lluvia, obras y fallas durante el viaje.', 'Replantear el recorrido sin improvisar una exposición mayor.', 43, 3, [$unitIds[1]]),
        ]);
    }

    private function upsertRouteCurriculum(string $courseId, string $prerequisiteModuleId): void
    {
        $module = $this->routeModule($prerequisiteModuleId);
        $now = now();
        DB::table('academic_course_modules')->updateOrInsert(['id' => $module->id], ['course_id' => $courseId, 'code' => $module->code, 'title' => $module->title, 'description' => $module->description, 'objectives' => $module->objectives, 'duration_minutes' => $module->durationMinutes, 'position' => 2, 'created_at' => $now, 'updated_at' => $now]);
        foreach ($module->units as $unit) {
            DB::table('academic_course_units')->updateOrInsert(['id' => $unit->id], ['module_id' => $module->id, 'code' => $unit->code, 'title' => $unit->title, 'description' => $unit->description, 'objectives' => $unit->objectives, 'duration_minutes' => $unit->durationMinutes, 'position' => $unit->position, 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('academic_module_prerequisites')->updateOrInsert(['module_id' => $module->id, 'prerequisite_module_id' => $prerequisiteModuleId]);
        foreach ($module->units as $index => $unit) {
            if ($index > 0) DB::table('academic_unit_prerequisites')->updateOrInsert(['unit_id' => $unit->id, 'prerequisite_unit_id' => $module->units[$index - 1]->id]);
        }
    }

    private function replaceRouteContent(string $courseId, string $competencyId): void
    {
        $units = $this->routeModule('00000000-0000-0000-0000-000000000000')->units;
        $lessons = $this->routeLessons($competencyId);
        foreach ($units as $index => $unit) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($courseId, $unit->id, [$lessons[$index * 2], $lessons[$index * 2 + 1]]));
        }
    }

    /** @return list<LessonInput> */
    private function routeLessons(string $competencyId): array
    {
        return [
            $this->routeLesson('BICI-RUTA-COMPARA', 'Reto 1: La más corta no siempre es la mejor', 1, 19, 'visual_exploration', 'Comparar rutas usando criterios de seguridad.', $competencyId, ['CICLISTA.RUTA.ELIGE'], [
                $this->text(1, 'Cuatro preguntas antes de salir', "Compará **velocidad del tránsito, espacio disponible, estado de la superficie y cantidad de conflictos**. Una ruta algo más larga puede ofrecer mejor visibilidad y menos decisiones simultáneas. Para personas menores, la planificación se realiza con una persona adulta."),
                $this->scenario(2, 'Dos caminos al parque', 'La ruta corta tiene tránsito rápido y entradas de comercios. La alternativa es más larga, pero tiene menor velocidad y mejor visibilidad.', '¿Qué criterio debe pesar más?', [
                    ['id' => 'corta', 'label' => 'La distancia más corta', 'feedback' => 'La distancia no refleja por sí sola la exposición ni la complejidad.', 'correct' => false],
                    ['id' => 'margen', 'label' => 'La ruta con menos conflictos y mayor margen', 'feedback' => 'Correcto. Comparás la calidad del recorrido, no solo sus metros.', 'correct' => true],
                    ['id' => 'costumbre', 'label' => 'La que usa la mayoría', 'feedback' => 'La popularidad no sustituye la evaluación personal.', 'correct' => false],
                ]),
                $this->scenario(3, 'La ruta cambia con la hora', 'Una calle es tranquila por la mañana, pero a la salida de clases se llena de autobuses, vehículos estacionados y personas cruzando.', '¿Qué demuestra una planificación completa?', [
                    ['id' => 'misma', 'label' => 'Usar siempre la misma ruta porque ya es conocida', 'feedback' => 'Conocer el recorrido no elimina los conflictos que aparecen en otros horarios.', 'correct' => false],
                    ['id' => 'horario', 'label' => 'Comparar también horario, tránsito y visibilidad antes de elegir', 'feedback' => 'Correcto. Una ruta se evalúa según las condiciones previstas para ese momento.', 'correct' => true],
                    ['id' => 'acera', 'label' => 'Subir a la acera si hay demasiados vehículos', 'feedback' => 'Trasladar el conflicto al espacio peatonal no sustituye una ruta planificada.', 'correct' => false],
                ]),
                $this->text(4, 'Mapa sin datos privados', "Dibujá dos rutas imaginarias. Marcá zonas protegidas, intersecciones, pendientes y superficies variables. No incluyás tu dirección, horarios ni lugares que permitan identificarte."),
            ]),
            $this->routeLesson('BICI-RUTA-PENDIENTE', 'Reto 2: Subidas, bajadas y control', 2, 19, 'dilemma', 'Elegir una ruta compatible con habilidad y control de velocidad.', $competencyId, ['CICLISTA.RUTA.ELIGE', 'CICLISTA.RUTA.REPLANIFICA'], [
                $this->text(1, 'La pendiente cambia la exigencia', "En una bajada aumenta la velocidad y la distancia para detenerse; en una subida puede disminuir la estabilidad. Evaluá frenos, superficie, visibilidad y tu experiencia. Bajarte y caminar por un espacio apropiado también puede ser una decisión competente."),
                $this->scenario(2, 'Bajada mojada', 'La ruta habitual tiene una bajada pronunciada y acaba de llover.', '¿Qué opción conserva más control?', [
                    ['id' => 'impulso', 'label' => 'Tomar impulso para terminar rápido', 'feedback' => 'Más velocidad reduce el margen sobre una superficie variable.', 'correct' => false],
                    ['id' => 'alternativa', 'label' => 'Elegir otra ruta o avanzar fuera de la bicicleta donde sea seguro', 'feedback' => 'Correcto. Adaptás el medio y la ruta a las condiciones.', 'correct' => true],
                    ['id' => 'freno', 'label' => 'Bajar frenando bruscamente', 'feedback' => 'Una frenada brusca puede reducir estabilidad y adherencia.', 'correct' => false],
                ]),
                $this->scenario(3, 'Subida con carga', 'Una mochila pesada cambia el equilibrio de Luna en una subida y empieza a zigzaguear para mantener el impulso.', '¿Qué decisión conserva control y espacio?', [
                    ['id' => 'impulso', 'label' => 'Pedalear con más fuerza y ocupar más ancho', 'feedback' => 'El zigzag vuelve impredecible la trayectoria y puede acercarla a otros movimientos.', 'correct' => false],
                    ['id' => 'detener', 'label' => 'Detenerse en un punto seguro y continuar caminando o ajustar la carga', 'feedback' => 'Correcto. Cambia la forma de avanzar antes de perder estabilidad.', 'correct' => true],
                    ['id' => 'soltar', 'label' => 'Soltar una mano para sostener la mochila', 'feedback' => 'Usar una mano para la carga reduce todavía más el control de la bicicleta.', 'correct' => false],
                ]),
                $this->text(4, 'Escala personal', "Clasificá pendientes imaginarias en: puedo controlarla, necesito acompañamiento o elijo otra ruta. La categoría puede cambiar con lluvia, carga o cansancio."),
            ]),
            $this->routeLesson('BICI-INTERSECCION', 'Reto 3: Llegá preparado a la intersección', 1, 21, 'web_simulation', 'Reducir y recopilar información antes de cruzar trayectorias.', $competencyId, ['CICLISTA.RUTA.INTERSECCION'], [
                $this->text(1, 'Decidir antes del punto de conflicto', "Las intersecciones reúnen giros, cruces y diferencias de velocidad. Llegá con tiempo para observar señales, ruedas delanteras, peatones y posibles giros. La decisión segura se prepara **antes**, no cuando ya estás dentro."),
                $this->scenario(2, 'Vehículo que podría girar', 'Un automóvil a tu lado reduce velocidad al acercarse a la esquina, pero no ves con claridad a la persona conductora.', '¿Qué anticipás?', [
                    ['id' => 'recto', 'label' => 'Que seguirá recto porque no señaló', 'feedback' => 'La ausencia de señal no garantiza la trayectoria.', 'correct' => false],
                    ['id' => 'giro', 'label' => 'Que podría girar y cruzar tu trayectoria', 'feedback' => 'Correcto. Reducís y evitás permanecer a su lado.', 'correct' => true],
                    ['id' => 'acelerar', 'label' => 'Que conviene adelantarlo', 'feedback' => 'Competir te acerca al conflicto con menos tiempo.', 'correct' => false],
                ]),
                $this->scenario(3, 'Salida desde una calle lateral', 'Luna se aproxima a una intersección y un muro oculta una calle lateral de la que podría salir un vehículo.', '¿Cuándo debe resolver el punto ciego?', [
                    ['id' => 'dentro', 'label' => 'Cuando ya esté dentro de la intersección', 'feedback' => 'Dentro del conflicto queda menos espacio y tiempo para reaccionar.', 'correct' => false],
                    ['id' => 'antes', 'label' => 'Antes de llegar: reducir, buscar visión y preparar una detención', 'feedback' => 'Correcto. La incertidumbre se atiende antes de cruzar trayectorias.', 'correct' => true],
                    ['id' => 'timbre', 'label' => 'Tocar el timbre y mantener la velocidad', 'feedback' => 'Advertir no garantiza que otra persona escuche o pueda detenerse.', 'correct' => false],
                ]),
                $this->text(4, 'Lectura de ruedas', "Con fotografías o una maqueta, observá orientación de ruedas, posición y velocidad. Proponé más de un movimiento posible y una respuesta que siga siendo segura si tu predicción falla."),
            ]),
            $this->routeLesson('BICI-PARADAS', 'Reto 4: Paradas, puertas y personas', 2, 21, 'dilemma', 'Anticipar movimientos alrededor de autobuses y vehículos estacionados.', $competencyId, ['CICLISTA.RUTA.INTERSECCION', 'CICLISTA.RUTA.POSICION'], [
                $this->text(1, 'Una zona con muchas sorpresas', "Cerca de paradas pueden aparecer personas por delante o detrás del autobús; junto a vehículos estacionados pueden abrirse puertas. Reducí, aumentá distancia y no atravieses un espacio cuya salida no podés ver."),
                $this->scenario(2, 'Autobús detenido', 'Un autobús recibe pasajeros y el espacio restante es estrecho.', '¿Qué opción deja una salida segura?', [
                    ['id' => 'hueco', 'label' => 'Pasar por el hueco antes de que arranque', 'feedback' => 'El espacio puede cerrarse y hay movimientos ocultos.', 'correct' => false],
                    ['id' => 'esperar', 'label' => 'Reducir y esperar detrás con distancia', 'feedback' => 'Correcto. Evitás el punto ciego y recuperás información.', 'correct' => true],
                    ['id' => 'acera', 'label' => 'Subir a la acera para rodearlo', 'feedback' => 'Trasladarías el conflicto al espacio peatonal.', 'correct' => false],
                ]),
                $this->scenario(3, 'Pasajero que puede descender', 'Un vehículo se detiene junto a la acera y Luna observa movimiento en el asiento del acompañante.', '¿Qué conflicto debe anticipar?', [
                    ['id' => 'ninguno', 'label' => 'Ninguno, porque el vehículo ya se detuvo', 'feedback' => 'Una detención puede preceder la apertura de una puerta o el descenso de una persona.', 'correct' => false],
                    ['id' => 'puerta', 'label' => 'La puerta puede abrirse; debe reducir y conservar distancia lateral', 'feedback' => 'Correcto. Luna usa la señal previa para evitar quedar dentro de la zona de apertura.', 'correct' => true],
                    ['id' => 'acelerar', 'label' => 'Acelerar antes de que la persona abra', 'feedback' => 'Competir contra una puerta posible reduce el tiempo y el margen de respuesta.', 'correct' => false],
                ]),
                $this->text(4, 'Zona de incertidumbre', "En una maqueta, marcá alrededor de un autobús: puertas, frente, parte trasera y costados. Elegí una posición desde la que puedas esperar sin quedar atrapado."),
            ]),
            $this->routeLesson('BICI-RUTA-CAMBIA', 'Reto 5: Obras, lluvia y plan B', 1, 21, 'dilemma', 'Replantear el recorrido ante cambios inesperados.', $competencyId, ['CICLISTA.RUTA.POSICION', 'CICLISTA.RUTA.REPLANIFICA'], [
                $this->text(1, 'Cambiar de plan es una habilidad', "Una obra, inundación, falla mecánica o pérdida de luz puede volver inadecuada la ruta prevista. Detenete en un lugar protegido, evaluá alternativas y pedí apoyo. No resolvás la sorpresa entrando impulsivamente a un flujo desconocido."),
                $this->scenario(2, 'Paso bloqueado', 'Una obra elimina el espacio disponible y obliga a mezclarse con tránsito rápido.', '¿Qué decisión demuestra adaptación?', [
                    ['id' => 'rapido', 'label' => 'Atravesar rápido el tramo', 'feedback' => 'La velocidad no crea espacio ni visibilidad.', 'correct' => false],
                    ['id' => 'replanificar', 'label' => 'Detenerte protegido y elegir otra ruta o apoyo', 'feedback' => 'Correcto. Rehacés el plan antes de exponerte.', 'correct' => true],
                    ['id' => 'acera', 'label' => 'Usar la acera sin reducir', 'feedback' => 'Podrías crear peligro para peatones y seguir sin salida clara.', 'correct' => false],
                ]),
                $this->scenario(3, 'La batería de la luz se agota', 'Durante el recorrido comienza a oscurecer y la luz de la bicicleta deja de funcionar antes de lo previsto.', '¿Qué activa un plan B seguro?', [
                    ['id' => 'telefono', 'label' => 'Usar la luz del teléfono mientras pedalea', 'feedback' => 'El teléfono ocupa una mano, distrae y no reemplaza una luz instalada y orientada correctamente.', 'correct' => false],
                    ['id' => 'salir', 'label' => 'Salir del flujo en un lugar seguro y buscar apoyo o transporte alternativo', 'feedback' => 'Correcto. La pérdida de visibilidad cambia las condiciones y justifica terminar el recorrido montado.', 'correct' => true],
                    ['id' => 'rapido', 'label' => 'Aumentar la velocidad para llegar antes de que oscurezca más', 'feedback' => 'Con menos visibilidad se necesita más tiempo para reaccionar, no menos.', 'correct' => false],
                ]),
                $this->text(4, 'Tarjeta de contingencia', "Definí qué harías si falla la bicicleta, cambia el clima o se bloquea la ruta. Incluí un lugar seguro para esperar y una persona de apoyo, sin guardar datos personales en la plataforma."),
            ]),
            $this->routeLesson('BICI-RUTA-MISION', 'Misión integradora: una ruta que puede cambiar', 2, 22, 'competency_challenge', 'Integrar planificación, intersecciones, posición y replanteamiento.', $competencyId, ['CICLISTA.RUTA.ELIGE', 'CICLISTA.RUTA.INTERSECCION', 'CICLISTA.RUTA.POSICION', 'CICLISTA.RUTA.REPLANIFICA'], [
                $this->text(1, 'Planificá sin quedar atrapado en el plan', "Elegí la ruta con criterios claros, prepará cada intersección, evitá puntos ciegos y mantené una alternativa. Una ruta segura es la que podés modificar sin improvisar una maniobra peligrosa."),
                $this->scenario(2, 'Recorrido escolar cambiante', 'La ruta tiene una obra, lluvia ligera y una fila de vehículos cerca de la entrada.', '¿Cuál es el primer paso?', [
                    ['id' => 'continuar', 'label' => 'Continuar hasta encontrar el problema de cerca', 'feedback' => 'Acercarte puede dejarte sin espacio para decidir.', 'correct' => false],
                    ['id' => 'protegido', 'label' => 'Detenerte protegido y reconstruir la ruta completa', 'feedback' => 'Correcto. Evaluás los cambios antes de entrar en ellos.', 'correct' => true],
                    ['id' => 'fila', 'label' => 'Pasar entre la fila y la acera', 'feedback' => 'Es un espacio con puertas, peatones y salidas ocultas.', 'correct' => false],
                ]),
                $this->scenario(3, 'Intersección con giro', 'Un vehículo grande reduce junto a vos antes de una esquina.', '¿Dónde conservás mejor margen?', [
                    ['id' => 'lado', 'label' => 'A su lado para que note tu presencia', 'feedback' => 'Podés permanecer en un punto ciego y dentro del giro.', 'correct' => false],
                    ['id' => 'detras', 'label' => 'Detrás, con distancia y fuera de su trayectoria', 'feedback' => 'Correcto. Recuperás visión y una salida.', 'correct' => true],
                    ['id' => 'delante', 'label' => 'Acelerando para quedar delante', 'feedback' => 'Competir reduce tiempo para resolver el conflicto.', 'correct' => false],
                ]),
                $this->scenario(4, 'Falla durante el viaje', 'El freno comienza a responder de forma irregular.', '¿Qué completa una respuesta preventiva?', [
                    ['id' => 'despacio', 'label' => 'Seguir despacio hasta el destino', 'feedback' => 'La falla puede empeorar y comprometer la detención.', 'correct' => false],
                    ['id' => 'salir', 'label' => 'Salir del flujo en un punto seguro y no continuar montado', 'feedback' => 'Correcto. Detenés el recorrido antes de perder control.', 'correct' => true],
                    ['id' => 'unfreno', 'label' => 'Usar solamente el otro freno', 'feedback' => 'Dependés de un sistema reducido y una falla no evaluada.', 'correct' => false],
                ]),
                $this->text(5, 'Práctica de transferencia y Pasaporte Vial', "Con acompañamiento apropiado, compará un recorrido conocido y explicá: por qué lo elegís, dónde reducirías, qué punto ciego evitarías y qué cambio activaría tu plan B.\n\nRealizá la observación sin registrar direcciones, horarios ni datos privados. Las personas menores deben practicar con una persona adulta. La finalización digital, la práctica observada y tu reflexión se incorporan como evidencias distintas en el **Pasaporte Vial**."),
            ]),
        ];
    }

    /** @param list<string> $indicators @param list<ContentBlockInput> $blocks */
    private function routeLesson(string $code, string $title, int $position, int $minutes, string $experience, string $objective, string $competencyId, array $indicators, array $blocks): LessonInput
    {
        $id = $this->stableId($code);

        return new LessonInput($id, $code, $title, $objective, $minutes, $position, $blocks, LessonLearningDesign::fromArray([
            'stage' => 'pending_review', 'jurisdictions' => ['GLOBAL', 'CR'], 'experience_type' => $experience,
            'behavior_objective' => $objective, 'competency_id' => $competencyId, 'subcompetency_code' => 'CICLISTA.RUTA',
            'indicator_codes' => $indicators,
            'evidence_rules' => array_map(static fn (string $indicator): array => ['indicator_code' => $indicator, 'event_type' => 'lesson_completed', 'minimum_observations' => 1, 'weight' => 1], $indicators),
            'requires_guardian' => true,
            'normative_sources' => [['url' => 'https://pgrweb.go.cr/Scij/Busqueda/Normativa/Normas/nrm_texto_completo.aspx?nValor1=1&nValor2=73504&param1=NRM&strTipM=FN', 'reviewed_at' => '2026-09-08']],
            'version' => 2,
        ]));
    }

    private function stableId(string $suffix): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_DNS, 'edudrive.'.self::COURSE_CODE.'.'.$suffix)->toString();
    }


    /** @return list<LessonInput> */
    private function lessons(string $competencyId, array $preservedIds = []): array
    {
        return [
            $this->lesson('RETO-REVISION', 'Reto 1: Cinco puntos antes de salir', 1, 18, 'guided_practice', 'Comprobar que la persona y la bicicleta están listas.', $competencyId, ['CICLISTA.REVISA'], [
                $this->text(1, 'La salida empieza antes de pedalear', "Una bicicleta pequeña también es un vehículo y necesita revisión. Antes de moverte, comprueba **casco, frenos, llantas, piezas y ajuste**. El casco debe quedar nivelado, bien abrochado y sin moverse sobre la cabeza.\n\nAprieta ambos frenos por separado, revisa que las llantas tengan aire y busca piezas flojas. Si algo falla, no improvises: pide ayuda y repara primero."),
                $this->scenario(2, 'El freno que casi funciona', 'Al probar la bicicleta, el freno trasero llega hasta el manubrio y apenas detiene la rueda.', '¿Cuál es la decisión segura?', [
                    ['id' => 'despacio', 'label' => 'Salir, pero pedalear muy despacio', 'feedback' => 'Un freno defectuoso puede impedir detenerse incluso a baja velocidad.', 'correct' => false],
                    ['id' => 'reparar', 'label' => 'No salir y pedir que lo reparen', 'feedback' => '¡Correcto! La revisión sirve para detener el viaje antes de que el defecto se convierta en peligro.', 'correct' => true],
                    ['id' => 'un-freno', 'label' => 'Usar solamente el freno delantero', 'feedback' => 'Depender de un solo freno reduce el control y puede provocar una caída.', 'correct' => false],
                ]),
                $this->scenario(3, 'Una llanta pierde aire', 'La llanta parece inflada, pero al presionarla cede demasiado y tiene una pequeña grieta lateral.', '¿Qué demuestra una revisión útil?', [
                    ['id' => 'inflar', 'label' => 'Inflarla mucho y salir de inmediato', 'feedback' => 'Agregar aire no corrige una grieta y una presión incorrecta también puede afectar el control.', 'correct' => false],
                    ['id' => 'detener', 'label' => 'No salir hasta que una persona competente revise llanta y presión', 'feedback' => 'Correcto. La revisión sirve para detener el recorrido cuando aparece una condición insegura.', 'correct' => true],
                    ['id' => 'probar', 'label' => 'Probarla pedaleando en la calle', 'feedback' => 'La calzada no es el lugar para comprobar una falla que ya fue detectada.', 'correct' => false],
                ]),
                $this->text(4, 'Práctica en espacio protegido', "Con la bicicleta quieta y una persona adulta si la necesitas, recorre la lista **C-F-L-P-A**: casco, frenos, llantas, piezas y ajuste. Señala cada elemento y explica qué problema estás buscando.\n\nNo realices esta revisión en la calzada ni pruebes una bicicleta que ya detectaste como insegura."),
            ], $preservedIds['RETO-REVISION'] ?? null),
            $this->lesson('RETO-VISIBLE', 'Reto 1: Que te vean a tiempo', 1, 15, 'visual_exploration', 'Elegir recursos que aumentan la visibilidad.', $competencyId, ['CICLISTA.VISIBLE'], [
                $this->text(1, 'Ser visible también es comunicar', "Ver un vehículo no significa que su conductor ya te vio. Usa ropa llamativa y elementos reflectivos; cuando corresponde, lleva luz blanca o amarilla al frente y elementos visibles atrás. En lluvia, neblina, sombra o anochecer, la visibilidad cambia rápidamente.\n\nLa visibilidad ayuda, pero no vuelve segura cualquier maniobra: mantén distancia, respeta las señales y evita aparecer de repente desde puntos ocultos."),
                $this->scenario(2, 'Regreso más tarde de lo previsto', 'La ruta comenzó con luz de día, pero está oscureciendo y la bicicleta no tiene luz delantera.', '¿Qué protege mejor a la persona ciclista?', [
                    ['id' => 'telefono', 'label' => 'Iluminar el camino sosteniendo el teléfono', 'feedback' => 'Esto ocupa una mano, distrae y no reemplaza una luz instalada correctamente.', 'correct' => false],
                    ['id' => 'detener', 'label' => 'Detenerse en un lugar seguro y buscar transporte o una luz adecuada', 'feedback' => '¡Buena decisión! Cambiar el plan es preferible a circular sin poder ver ni ser visible.', 'correct' => true],
                    ['id' => 'rapido', 'label' => 'Pedalear más rápido para llegar pronto', 'feedback' => 'Más velocidad deja menos tiempo para ver, reaccionar y detenerse.', 'correct' => false],
                ]),
                $this->scenario(3, 'Sombra después del sol', 'Luna pasa de una zona muy iluminada a un tramo bajo árboles donde su ropa y bicicleta se confunden con el fondo.', '¿Qué debe reconocer?', [
                    ['id' => 'dia', 'label' => 'Que durante el día siempre será visible', 'feedback' => 'Los contrastes, las sombras y el fondo pueden reducir la detección incluso de día.', 'correct' => false],
                    ['id' => 'adaptar', 'label' => 'Que debe aumentar visibilidad, distancia y anticipación antes del tramo', 'feedback' => 'Correcto. Ser visible depende también del entorno y se prepara antes de entrar en la sombra.', 'correct' => true],
                    ['id' => 'centro', 'label' => 'Que debe moverse bruscamente para llamar la atención', 'feedback' => 'Los movimientos repentinos pueden volver impredecible su trayectoria.', 'correct' => false],
                ]),
                $this->text(4, 'Experimento de visibilidad', "Coloca, sin salir a la vía, objetos claros, oscuros y reflectivos a diferentes distancias. Observa cómo cambia su visibilidad con poca luz. Conversa: **¿qué se ve primero y por qué?**\n\nLa meta no es comprar más objetos: es aprender a reconocer cuándo otra persona podría no haberte detectado todavía."),
            ], $preservedIds['RETO-VISIBLE'] ?? null),
            $this->lesson('RETO-ATENCION', 'Reto 1: ojos, oídos y decisiones', 1, 21, 'competency_challenge', 'Mantener atención y anticipar una situación cambiante.', $competencyId, ['CICLISTA.REVISA', 'CICLISTA.VISIBLE', 'CICLISTA.ATIENDE', 'CICLISTA.ANTICIPA'], [
                $this->text(1, 'Tu atención es parte del equipo', "Los audífonos, el teléfono y llevar objetos en las manos reducen la información disponible y el control de la bicicleta. Mira hacia adelante, escucha el entorno, sujeta el manubrio y comunica tus movimientos con anticipación.\n\nAnte una entrada, vehículo estacionado, persona peatona o superficie mojada, reduce velocidad y prepara una salida segura. La prioridad no es conservar el ritmo: es conservar opciones."),
                $this->scenario(2, 'La puerta inesperada', 'Una bicicleta se acerca a un automóvil estacionado. Hay una persona dentro y podría abrir la puerta.', '¿Qué respuesta demuestra anticipación?', [
                    ['id' => 'igual', 'label' => 'Mantener velocidad porque el automóvil está detenido', 'feedback' => 'Un automóvil detenido todavía puede generar riesgo si abre una puerta o inicia la marcha.', 'correct' => false],
                    ['id' => 'espacio', 'label' => 'Reducir velocidad, crear espacio y observar antes de pasar', 'feedback' => '¡Reto superado! Conservar espacio y velocidad baja permite reaccionar sin una maniobra brusca.', 'correct' => true],
                    ['id' => 'audifonos', 'label' => 'Subir el volumen para concentrarse', 'feedback' => 'Bloquear sonidos elimina señales útiles del entorno y no mejora el control.', 'correct' => false],
                ]),
                $this->scenario(3, 'Objeto que cae al pedalear', 'Una botella mal asegurada cae cerca de la rueda mientras Luna circula.', '¿Qué respuesta conserva el control?', [
                    ['id' => 'recoger', 'label' => 'Inclinarse para recogerla sin detenerse', 'feedback' => 'Soltar el control y cambiar el equilibrio en movimiento puede provocar una caída.', 'correct' => false],
                    ['id' => 'detener', 'label' => 'Mantener la trayectoria, detenerse en un lugar seguro y recogerla después', 'feedback' => 'Correcto. Primero controla y sale de la circulación; el objeto se atiende sin improvisar.', 'correct' => true],
                    ['id' => 'frenar', 'label' => 'Frenar bruscamente donde cayó', 'feedback' => 'Una detención repentina puede sorprender a quienes vienen detrás.', 'correct' => false],
                ]),
                $this->text(4, 'Plan personal de salida', "Antes de tu próximo recorrido, describe en voz alta: **qué revisarás, cómo te harás visible, qué distracciones guardarás y qué riesgos esperas encontrar**. Si eres menor, comparte el plan con la persona adulta que te acompaña.\n\nCompromiso vial: “Preparo, me hago visible, presto atención y cambio el plan si las condiciones no son seguras”."),
            ], $preservedIds['RETO-ATENCION'] ?? null),
        ];
    }

    /** @return list<LessonInput> */
    private function supplementalLessons(string $competencyId): array
    {
        return [
            $this->lesson('RETO-CASCO', 'Reto 2: Un casco que sí protege', 2, 13, 'guided_practice', 'Ajustar el casco y reconocer cuándo debe reemplazarse.', $competencyId, ['CICLISTA.REVISA'], [
                $this->text(1, 'Ajuste antes que apariencia', "El casco debe corresponder al tamaño de la persona, quedar nivelado y mantenerse firme al mover la cabeza. Las correas forman una **V alrededor de las orejas** y permiten abrir la boca sin que la hebilla quede floja.\n\nUn casco golpeado, agrietado o alterado puede no proteger como fue diseñado."),
                $this->scenario(2, 'Casco prestado', 'El único casco disponible es demasiado grande y se mueve sobre los ojos.', '¿Qué decisión protege mejor?', [
                    ['id' => 'ajustar', 'label' => 'Apretarlo al máximo y salir', 'feedback' => 'Las correas no corrigen una talla inadecuada.', 'correct' => false],
                    ['id' => 'esperar', 'label' => 'Esperar hasta tener uno de talla y ajuste adecuados', 'feedback' => 'Correcto. El equipo debe ajustarse antes de iniciar el recorrido.', 'correct' => true],
                    ['id' => 'gorra', 'label' => 'Usar una gorra debajo para rellenar', 'feedback' => 'Agregar objetos puede cambiar la posición y estabilidad del casco.', 'correct' => false],
                ]),
                $this->scenario(3, 'Casco después de una caída', 'El casco golpeó con fuerza el suelo durante una caída. Por fuera solo tiene una marca pequeña.', '¿Qué criterio es seguro?', [
                    ['id' => 'apariencia', 'label' => 'Seguir usándolo porque no está partido', 'feedback' => 'El daño que reduce la protección puede no ser visible desde afuera.', 'correct' => false],
                    ['id' => 'reemplazar', 'label' => 'Retirarlo de uso y seguir la indicación del fabricante para reemplazarlo', 'feedback' => 'Correcto. Después de un impacto importante no se confía únicamente en la apariencia.', 'correct' => true],
                    ['id' => 'pegar', 'label' => 'Cubrir la marca con cinta', 'feedback' => 'La cinta no restaura la capacidad del casco para absorber otro impacto.', 'correct' => false],
                ]),
                $this->text(4, 'Comprobación acompañada', "Frente a un espejo y fuera de la vía, revisá nivel, movimiento, correas y hebilla. Para niñas y niños, una persona adulta confirma el ajuste final."),
            ]),
            $this->lesson('RETO-COMUNICA', 'Reto 2: Movimientos que se entienden', 2, 13, 'guided_practice', 'Comunicar cambios de dirección de forma anticipada y controlada.', $competencyId, ['CICLISTA.VISIBLE', 'CICLISTA.ATIENDE'], [
                $this->text(1, 'Ser predecible también te hace visible', "Una luz ayuda a detectar tu presencia; una posición estable y una señal anticipada ayudan a comprender qué harás. Antes de cambiar de dirección: observá, reducí si hace falta, señalá solo cuando mantengás control y volvé ambas manos al manubrio."),
                $this->scenario(2, 'Giro mientras frenás', 'Necesitás girar, pero la superficie está mojada y todavía debés reducir velocidad.', '¿Qué secuencia conserva más control?', [
                    ['id' => 'todo', 'label' => 'Señalar y frenar fuerte al mismo tiempo', 'feedback' => 'Combinar una mano libre con frenado fuerte sobre superficie mojada reduce estabilidad.', 'correct' => false],
                    ['id' => 'preparar', 'label' => 'Reducir antes, comprobar y señalar cuando la bicicleta esté estable', 'feedback' => 'Correcto. Preparás la maniobra antes de comunicarla.', 'correct' => true],
                    ['id' => 'sorpresa', 'label' => 'Girar sin señalar para conservar ambas manos', 'feedback' => 'El movimiento inesperado dificulta la reacción de otras personas.', 'correct' => false],
                ]),
                $this->scenario(3, 'No puede soltar una mano', 'Una persona ciclista todavía pierde estabilidad al intentar señalar con el brazo.', '¿Cómo debe actuar?', [
                    ['id' => 'practicar-via', 'label' => 'Practicar la señal durante el recorrido real', 'feedback' => 'La circulación no es un espacio de ensayo cuando todavía se pierde el control.', 'correct' => false],
                    ['id' => 'protegido', 'label' => 'Practicar fuera del tránsito y no hacer una maniobra que aún no controla', 'feedback' => 'Correcto. La comunicación nunca debe exigir perder estabilidad; primero se desarrolla la habilidad.', 'correct' => true],
                    ['id' => 'sin-mirar', 'label' => 'Girar sin señalar ni comprobar', 'feedback' => 'Eliminar la comunicación y la observación vuelve inesperado el movimiento.', 'correct' => false],
                ]),
                $this->text(4, 'Ensayo sin circulación', "En un espacio cerrado al tránsito, practicá mirar, reducir, señalar y recuperar el manubrio. Si perdés la trayectoria al soltar una mano, seguí practicando fuera de la vía."),
            ]),
            $this->lesson('RETO-SUPERFICIE', 'Reto 2: El suelo también cambia', 2, 15, 'web_simulation', 'Adaptar velocidad y trayectoria a superficies con menor adherencia.', $competencyId, ['CICLISTA.ATIENDE', 'CICLISTA.ANTICIPA'], [
                $this->text(1, 'Adherencia y equilibrio', "Agua, arena, grava, hojas, tapas metálicas y huecos cambian el contacto de las llantas con el suelo. Mirá hacia adelante, reducí antes de la zona difícil y evitá giros o frenadas bruscas sobre ella."),
                $this->scenario(2, 'Grava en la curva', 'Al acercarte a una curva observás grava suelta ocupando parte de tu trayectoria.', '¿Qué respuesta deja más opciones?', [
                    ['id' => 'frenar', 'label' => 'Entrar rápido y frenar dentro de la grava', 'feedback' => 'Frenar o girar bruscamente sobre grava puede reducir la adherencia.', 'correct' => false],
                    ['id' => 'antes', 'label' => 'Reducir antes, mantener distancia y elegir una trayectoria estable', 'feedback' => 'Correcto. Actuás antes de entrar a la superficie difícil.', 'correct' => true],
                    ['id' => 'saltar', 'label' => 'Intentar saltar la zona', 'feedback' => 'Una maniobra improvisada añade pérdida de control.', 'correct' => false],
                ]),
                $this->scenario(3, 'Tapa metálica mojada', 'Después de llover, una tapa metálica ocupa parte de la trayectoria y Luna todavía necesita girar.', '¿Qué preparación reduce el riesgo?', [
                    ['id' => 'girar-encima', 'label' => 'Girar y frenar mientras pasa sobre la tapa', 'feedback' => 'El metal mojado puede ofrecer menos adherencia, especialmente al girar o frenar.', 'correct' => false],
                    ['id' => 'antes', 'label' => 'Reducir antes y elegir una trayectoria estable sin maniobras bruscas sobre ella', 'feedback' => 'Correcto. La velocidad y dirección se preparan antes de alcanzar la superficie variable.', 'correct' => true],
                    ['id' => 'acelerar', 'label' => 'Acelerar para cruzarla en menos tiempo', 'feedback' => 'Más velocidad deja menos margen si la llanta pierde adherencia.', 'correct' => false],
                ]),
                $this->text(4, 'Mapa de superficies', "En un patio o imagen, clasificá superficies como normales, variables o no transitables. Explicá dónde reducirías antes y dónde cambiarías completamente la ruta."),
            ]),
        ];
    }

    /** @return list<LessonInput> */
    private function advancedLessons(string $competencyId): array
    {
        return [
            $this->lesson('RETO-AJUSTE-CARGA', 'Reto 3: Bicicleta, cuerpo y carga', 3, 13, 'dilemma', 'Comprobar que ajuste y carga permitan controlar la bicicleta.', $competencyId, ['CICLISTA.REVISA', 'CICLISTA.ATIENDE'], [
                $this->text(1, 'Control antes de capacidad', "El asiento, el manubrio y el alcance de los frenos deben permitir una postura controlada. Una mochila suelta, una bolsa en el manubrio o un objeto en la mano pueden alterar equilibrio, dirección o frenado."),
                $this->scenario(2, 'Bolsa en el manubrio', 'Una bolsa pesada cuelga de un lado del manubrio y roza la rueda al girar.', '¿Qué hacés antes de salir?', [
                    ['id' => 'equilibrar', 'label' => 'Compensar con el cuerpo', 'feedback' => 'El movimiento de la bolsa puede cambiar de forma imprevisible.', 'correct' => false],
                    ['id' => 'asegurar', 'label' => 'Retirarla o asegurar la carga en un sistema apropiado', 'feedback' => 'Correcto. Las manos y la dirección quedan disponibles.', 'correct' => true],
                    ['id' => 'mano', 'label' => 'Sostenerla con una mano', 'feedback' => 'Eso reduce control y capacidad para frenar o señalizar.', 'correct' => false],
                ]),
                $this->scenario(3, 'Bicicleta demasiado grande', 'Luna apenas alcanza el suelo y debe estirarse para accionar correctamente los frenos.', '¿Qué decisión prioriza el control?', [
                    ['id' => 'adaptarse', 'label' => 'Usarla despacio hasta acostumbrarse', 'feedback' => 'La baja velocidad no corrige la dificultad para sostenerse o frenar.', 'correct' => false],
                    ['id' => 'ajustar', 'label' => 'No salir hasta usar una bicicleta correctamente ajustada a su cuerpo', 'feedback' => 'Correcto. La talla y el ajuste deben permitir controlar dirección, equilibrio y frenado.', 'correct' => true],
                    ['id' => 'punta', 'label' => 'Apoyarse solo con la punta del pie cuando se detenga', 'feedback' => 'Improvisar una postura no garantiza estabilidad en una detención inesperada.', 'correct' => false],
                ]),
                $this->text(4, 'Prueba estacionaria', "Con la bicicleta quieta, comprobá que alcanzás ambos frenos, girás el manubrio sin obstáculos y la carga no puede entrar en ruedas o cadena."),
            ]),
            $this->lesson('RETO-PUNTOS-CIEGOS', 'Reto 3: Hacete visible sin confiarte', 3, 13, 'dilemma', 'Reconocer puntos ciegos y evitar permanecer en ellos.', $competencyId, ['CICLISTA.VISIBLE', 'CICLISTA.ANTICIPA'], [
                $this->text(1, 'Vehículos grandes, información incompleta', "Autobuses y camiones tienen zonas desde las que una persona conductora puede no detectar una bicicleta. Si no podés ver claramente a quien conduce o sus espejos, no asumás que ya te vio. Conservá distancia y evitá permanecer junto al vehículo, especialmente cerca de giros."),
                $this->scenario(2, 'Autobús antes de la esquina', 'Pedaleás junto a un autobús que podría girar y tu bicicleta queda cerca de su costado.', '¿Cuál es la opción preventiva?', [
                    ['id' => 'pasar', 'label' => 'Acelerar para pasar antes de la esquina', 'feedback' => 'Competir reduce tiempo y puede mantenerte en una zona difícil de ver.', 'correct' => false],
                    ['id' => 'distancia', 'label' => 'Reducir y quedar detrás con distancia antes del giro', 'feedback' => 'Correcto. Salís del conflicto y recuperás información.', 'correct' => true],
                    ['id' => 'tocar', 'label' => 'Acercarte y llamar la atención', 'feedback' => 'Ser ruidoso no garantiza detección ni crea espacio físico.', 'correct' => false],
                ]),
                $this->scenario(3, 'Camión que retrocede', 'Un camión inicia una maniobra de reversa cerca de una entrada. Luna no puede ver el rostro de la persona conductora ni saber si la detectó.', '¿Qué crea una salida segura?', [
                    ['id' => 'pasar-detras', 'label' => 'Pasar rápido detrás antes de que cierre el espacio', 'feedback' => 'La zona posterior puede ser un punto ciego y la trayectoria puede cambiar.', 'correct' => false],
                    ['id' => 'distancia', 'label' => 'Detenerse lejos de la maniobra y esperar en un punto visible', 'feedback' => 'Correcto. Luna evita la zona de movimiento y no depende de haber sido detectada.', 'correct' => true],
                    ['id' => 'timbre', 'label' => 'Tocar el timbre y continuar', 'feedback' => 'Una señal sonora no garantiza que se escuche ni que el vehículo pueda detenerse a tiempo.', 'correct' => false],
                ]),
                $this->text(4, 'Maqueta de visibilidad', "Usá cajas como vehículos y una figura como bicicleta. Explorá qué posiciones desaparecen desde el asiento imaginario y elegí lugares que conserven distancia y salida."),
            ]),
            $this->lesson('MISION-BICI-INTEGRADORA', 'Misión integradora: prepará, comunicá y decidí', 3, 15, 'competency_challenge', 'Integrar revisión, visibilidad, atención y anticipación en un recorrido.', $competencyId, ['CICLISTA.REVISA', 'CICLISTA.VISIBLE', 'CICLISTA.ATIENDE', 'CICLISTA.ANTICIPA'], [
                $this->text(1, 'Tu secuencia completa', "**Revisá persona y bicicleta → evaluá luz y ruta → retirás distractores → comunicá con anticipación → conservá margen → cambiá el plan si cambian las condiciones.**"),
                $this->scenario(2, 'Salida con cambio de clima', 'La revisión está completa, pero comienza a oscurecer y la luz delantera no funciona.', '¿Qué integra mejor la rutina?', [
                    ['id' => 'seguir', 'label' => 'Salir porque la bicicleta funciona bien', 'feedback' => 'La revisión mecánica no compensa la pérdida de visibilidad.', 'correct' => false],
                    ['id' => 'cambiar', 'label' => 'Reparar la luz o elegir otra forma segura de viajar', 'feedback' => 'Correcto. Actualizás el plan con la condición nueva.', 'correct' => true],
                    ['id' => 'telefono', 'label' => 'Usar el teléfono como luz', 'feedback' => 'Ocupa una mano, distrae y no reemplaza iluminación adecuada.', 'correct' => false],
                ]),
                $this->scenario(3, 'Puerta y superficie mojada', 'Un automóvil estacionado tiene ocupantes y hay una franja mojada junto a él.', '¿Qué decisión conserva más margen?', [
                    ['id' => 'espacio', 'label' => 'Reducir antes y mantener distancia de puerta y superficie', 'feedback' => 'Correcto. Anticipás dos peligros sin una maniobra brusca.', 'correct' => true],
                    ['id' => 'rapido', 'label' => 'Pasar rápido entre ambos peligros', 'feedback' => 'La velocidad reduce las opciones si la puerta se abre.', 'correct' => false],
                    ['id' => 'frenar', 'label' => 'Frenar solamente cuando algo ocurra', 'feedback' => 'Esperar la emergencia elimina el valor de anticipar.', 'correct' => false],
                ]),
                $this->scenario(4, 'Grupo con audífonos', 'El grupo quiere salir rápido y te ofrece un audífono para compartir música.', '¿Qué respuesta demuestra liderazgo vial?', [
                    ['id' => 'aceptar', 'label' => 'Aceptar solo un audífono', 'feedback' => 'La música y conversación todavía compiten con pistas del entorno.', 'correct' => false],
                    ['id' => 'proponer', 'label' => 'Proponer guardar audífonos y revisar bicicletas antes de salir', 'feedback' => 'Correcto. Convertís el cuidado individual en una práctica colectiva.', 'correct' => true],
                    ['id' => 'atras', 'label' => 'Usarlo y viajar de último', 'feedback' => 'La posición en el grupo no recupera la atención auditiva.', 'correct' => false],
                ]),
                $this->text(5, 'Práctica y Pasaporte Vial', "En un espacio protegido, explicá tu revisión, una condición que te haría cambiar de plan y cómo comunicarías un movimiento. Realizá la práctica con acompañamiento apropiado para tu edad; la observación y reflexión complementan la evidencia digital."),
            ]),
        ];
    }

    /**
     * @param  list<string>  $indicators
     * @param  list<ContentBlockInput>  $blocks
     */
    private function lesson(string $code, string $title, int $position, int $minutes, string $experience, string $objective, string $competencyId, array $indicators, array $blocks, ?string $id = null): LessonInput
    {
        $id ??= Uuid::uuid5(Uuid::NAMESPACE_DNS, 'edudrive.'.self::COURSE_CODE.'.'.$code)->toString();

        return new LessonInput($id, $code, $title, $objective, $minutes, $position, $blocks, LessonLearningDesign::fromArray([
            'stage' => 'pending_review', 'jurisdictions' => ['GLOBAL', 'CR'], 'experience_type' => $experience,
            'behavior_objective' => $objective, 'competency_id' => $competencyId, 'subcompetency_code' => 'CICLISTA.RUTINA',
            'indicator_codes' => $indicators,
            'evidence_rules' => array_map(static fn (string $indicator): array => ['indicator_code' => $indicator, 'event_type' => 'lesson_completed', 'minimum_observations' => 1, 'weight' => 1], $indicators),
            'requires_guardian' => true,
            'normative_sources' => [
                ['url' => 'https://pgrweb.go.cr/Scij/Busqueda/Normativa/Normas/nrm_texto_completo.aspx?nValor1=1&nValor2=73504&param1=NRM&strTipM=FN', 'reviewed_at' => '2026-09-09'],
                ['url' => 'https://mep.go.cr/programas-proyectos/camino-seguro', 'reviewed_at' => '2026-09-09'],
                ['url' => 'https://www.csv.go.cr/seguridad-vial-virtual1', 'reviewed_at' => '2026-09-09'],
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
            'title' => $title, 'context' => $context, 'prompt' => $prompt,
            'accessible_text' => $context.' '.$prompt.' Todas las opciones y su retroalimentación pueden recorrerse con teclado.',
            'choices' => $choices,
        ]);
    }
}
