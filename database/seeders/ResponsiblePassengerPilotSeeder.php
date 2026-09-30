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
use Modules\Academic\Application\UseCases\ReplaceCourseCurriculumHandler;
use Modules\Academic\Application\UseCases\ReplaceUnitContentHandler;
use Modules\Academic\Application\UseCases\SubmitCourseForReviewHandler;
use Modules\Academic\Domain\ValueObjects\LessonLearningDesign;
use Ramsey\Uuid\Uuid;

final class ResponsiblePassengerPilotSeeder extends Seeder
{
    private const string COURSE_CODE = 'EDU-EXP-003';

    private const string COMPETENCY_CODE = 'PASAJERO-RESPONSABLE';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || DB::table('academic_courses')->where('code', self::COURSE_CODE)->exists()) {
            return;
        }

        $competencyId = $this->competencyId();
        $course = app(CreateCourseHandler::class)->handle(new CreateCourseCommand(
            code: self::COURSE_CODE,
            title: 'Misión Pasajero Responsable',
            description: 'Aprende a viajar protegido, subir y bajar con seguridad, cuidar a otras personas y actuar con calma cuando el recorrido cambia.',
            objectives: 'Usar correctamente los sistemas de protección; evitar distracciones; utilizar paradas seguras; convivir y responder ante imprevistos.',
            prerequisites: 'No requiere conducir. Las prácticas de personas menores se realizan con acompañamiento adulto y con el vehículo detenido.',
            modality: 'virtual',
            durationHours: 4,
        ));

        $moduleIds = [$this->id('MOD-PROTEGIDO'), $this->id('MOD-TRANSPORTE')];
        $unitIds = [
            $this->id('UNI-SUBIR'), $this->id('UNI-PROTECCION'), $this->id('UNI-CONVIVENCIA'),
            $this->id('UNI-PARADA'), $this->id('UNI-RECORRIDO'), $this->id('UNI-IMPREVISTOS'),
        ];
        $modules = [
            new CourseModuleInput($moduleIds[0], 'MISION-PASAJERO', 'Misión 1: Viajo protegido', 'Seis retos para preparar el viaje y conservar protección durante todo el recorrido.', 'Subir, ubicarse, usar protección y evitar conductas que interfieren con quien conduce.', 105, 1, [], [
                new CourseUnitInput($unitIds[0], 'ENTRAR-SALIR', '1. El viaje empieza antes de subir', 'Elegí un lugar seguro para entrar y salir.', 'Esperar, subir y descender sin quedar expuesto al tránsito.', 35, 1, []),
                new CourseUnitInput($unitIds[1], 'PROTECCION-PASAJERO', '2. Protección durante todo el viaje', 'Usá cinturón y sistemas apropiados para cada persona.', 'Comprobar ajuste, posición y permanencia de la protección.', 35, 2, [$unitIds[0]]),
                new CourseUnitInput($unitIds[2], 'CONVIVENCIA-INTERIOR', '3. Ayudar sin distraer', 'Comprendé cómo tu conducta afecta el viaje.', 'Mantener un ambiente predecible y comunicar necesidades con calma.', 35, 3, [$unitIds[1]]),
            ]),
            new CourseModuleInput($moduleIds[1], 'MISION-TRANSPORTE', 'Misión 2: Transporte compartido seguro', 'Seis retos para usar autobús, transporte escolar y paradas con criterio.', 'Planificar la espera, conservar estabilidad, respetar espacios y responder ante cambios.', 105, 2, [$moduleIds[0]], [
                new CourseUnitInput($unitIds[3], 'PARADA-SEGURA', '1. Esperar sin exponerse', 'Reconocé una parada y una espera seguras.', 'Mantener distancia del borde y preparar el ascenso sin empujar.', 35, 1, []),
                new CourseUnitInput($unitIds[4], 'DENTRO-TRANSPORTE', '2. Estabilidad y convivencia', 'Viajá con apoyo, atención y respeto.', 'Ubicar pertenencias y el cuerpo sin bloquear ni perder estabilidad.', 35, 2, [$unitIds[3]]),
                new CourseUnitInput($unitIds[5], 'CAMBIO-EMERGENCIA', '3. Cuando el plan cambia', 'Actuá con calma ante una parada distinta o emergencia.', 'Seguir indicaciones seguras, pedir apoyo y no improvisar.', 35, 3, [$unitIds[4]]),
            ]),
        ];
        app(ReplaceCourseCurriculumHandler::class)->handle(new ReplaceCourseCurriculumCommand($course->id, $modules));

        $lessons = $this->lessonSpecs();
        foreach ($unitIds as $unitIndex => $unitId) {
            $offset = $unitIndex * 2;
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($course->id, $unitId, [
                $this->lesson($lessons[$offset], 1, $competencyId),
                $this->lesson($lessons[$offset + 1], 2, $competencyId),
            ]));
        }

        if (! DemoCoursePublication::isReady($course->id)) {
            return;
        }

        app(SubmitCourseForReviewHandler::class)->handle(new SubmitCourseForReviewCommand($course->id));
        app(ApproveCourseHandler::class)->handle(new ApproveCourseCommand($course->id));
        app(PublishCourseHandler::class)->handle(new PublishCourseCommand($course->id));
    }

    private function competencyId(): string
    {
        $existing = DB::table('academic_competencies')->where('code', self::COMPETENCY_CODE)->value('id');
        if ($existing !== null) {
            return (string) $existing;
        }

        $competency = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand(
            self::COMPETENCY_CODE,
            'Se moviliza como pasajero de manera protegida, atenta y solidaria',
            'Utiliza sistemas de protección, evita interferencias y toma decisiones seguras en vehículos y transporte compartido.',
            'road_rules',
            'foundation',
        ));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($competency->id, 'PASAJERO.RUTINA', 'Aplica una rutina responsable antes, durante y después del viaje'));
        foreach ([
            ['PASAJERO.ACCESO', 'Sube y desciende desde un espacio protegido.'],
            ['PASAJERO.PROTEGE', 'Utiliza correctamente el sistema de protección apropiado.'],
            ['PASAJERO.CONVIVE', 'Evita distracciones y respeta las necesidades de otras personas.'],
            ['PASAJERO.TRANSPORTE', 'Espera y viaja con estabilidad en transporte compartido.'],
            ['PASAJERO.RESPONDE', 'Pide apoyo y sigue indicaciones ante cambios o emergencias.'],
        ] as [$code, $description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($competency->id, 'PASAJERO.RUTINA', $code, $description));
        }

        return $competency->id;
    }

    /** @return list<array<string, mixed>> */
    private function lessonSpecs(): array
    {
        return [
            $this->spec('PAS-SUBIR', 'Subir desde un lugar protegido', 'PASAJERO.ACCESO', 'guided_practice', 'Esperar hasta que el vehículo esté detenido y acceder desde el lado protegido.', 'La puerta no convierte cualquier lugar en una zona segura. Antes de subir, comprobá que el vehículo esté totalmente detenido, que exista espacio estable para esperar y que no debás caminar entre trayectorias activas.', ['Vehículo aún en movimiento', 'El vehículo se acerca lentamente con la puerta abierta.', 'Esperar lejos del borde hasta que se detenga por completo', 'Caminar junto al vehículo para ahorrar tiempo'], ['Ascenso junto a la calzada', 'La puerta disponible queda del lado del tránsito.', 'Pedir que el vehículo se ubique donde pueda accederse desde un espacio protegido', 'Subir rápido antes de que pase otro vehículo'], 'Ensayá la secuencia esperar, comprobar, acercarse y subir con un vehículo estacionado y una persona adulta.'),
            $this->spec('PAS-DESCENDER', 'Bajar y volver a observar', 'PASAJERO.ACCESO', 'dilemma', 'Descender sin aparecer de repente ante otras personas.', 'Al bajar cambia tu campo visual. Una puerta, un autobús o una fila de vehículos puede ocultarte. Descendé con calma, alcanzá un espacio protegido y volvé a observar antes de cruzar.', ['Bajar entre vehículos', 'El automóvil se detiene en una fila y la acera está al otro lado.', 'Permanecer dentro hasta llegar a un punto de descenso protegido', 'Bajar y cruzar entre los vehículos'], ['Después del autobús', 'Al bajar del autobús necesitás llegar al otro lado de la calle.', 'Esperar a que se aleje y usar un cruce con visibilidad', 'Cruzar inmediatamente por delante'], 'Con una maqueta, mostrá qué zonas quedan ocultas al bajar y dónde recuperarías visión.'),
            $this->spec('PAS-CINTURON', 'Cinturón durante todo el recorrido', 'PASAJERO.PROTEGE', 'guided_practice', 'Comprobar que el cinturón quede ajustado y permanezca colocado.', 'El cinturón funciona cuando está abrochado, sin torsiones y colocado sobre zonas resistentes del cuerpo. No se lleva bajo el brazo ni detrás de la espalda. Se mantiene puesto aunque el recorrido sea corto.', ['Recorrido de dos cuadras', 'El destino está muy cerca y el vehículo circula despacio.', 'Abrocharse antes de iniciar y mantenerlo hasta detenerse', 'No usarlo porque el viaje es corto'], ['Cinturón incómodo', 'La banda pasa cerca del cuello y la persona quiere colocarla bajo el brazo.', 'Detener el inicio y pedir ayuda para ajustar posición o sistema', 'Pasarla bajo el brazo para viajar cómodo'], 'Con el vehículo detenido, identificá banda diagonal, banda pélvica, hebilla y señales de torsión.'),
            $this->spec('PAS-SISTEMA-INFANTIL', 'Protección adecuada para cada cuerpo', 'PASAJERO.PROTEGE', 'visual_exploration', 'Reconocer que niñas y niños necesitan un sistema acorde con sus características.', 'La edad por sí sola no decide cómo viaja una persona menor. El sistema debe corresponder a su talla y peso, instalarse según instrucciones y permitir que el cinturón quede en una posición segura. La persona adulta responsable verifica el ajuste.', ['Asiento de persona adulta', 'El cinturón queda sobre el cuello y abdomen de una niña.', 'No iniciar hasta usar el sistema infantil apropiado y correctamente instalado', 'Colocar una almohada suelta para elevarla'], ['Sistema prestado', 'No se conoce el historial ni las instrucciones del sistema infantil.', 'Verificar compatibilidad, estado, instalación e historial antes de usarlo', 'Usarlo porque todos los sistemas protegen igual'], 'Una persona adulta revisa instrucciones y explica por qué el sistema elegido corresponde a esa niña o niño.'),
            $this->spec('PAS-DISTRAE', 'Acompañar sin distraer', 'PASAJERO.CONVIVE', 'dilemma', 'Evitar acciones que quitan atención o control a quien conduce.', 'Gritos, discusiones, mostrar una pantalla o pedir respuestas inmediatas pueden quitar atención a quien conduce. Una necesidad importante se comunica con una frase breve; lo demás puede esperar hasta un lugar seguro.', ['Video sorprendente', 'Una persona pasajera quiere mostrar un video a quien conduce.', 'Guardar la pantalla y comentarlo cuando el vehículo esté detenido', 'Acercar el teléfono para que lo vea rápidamente'], ['Objeto que cae', 'Una botella rueda cerca de los pedales de quien conduce.', 'Avisar con calma y pedir detenerse en un lugar seguro', 'Inclinarse entre los asientos para recogerla'], 'Clasificá conversaciones y acciones en: puede esperar, se comunica brevemente o requiere detener el viaje.'),
            $this->spec('PAS-CONVIVE', 'Espacio y respeto dentro del vehículo', 'PASAJERO.CONVIVE', 'community_observation', 'Ubicar cuerpo y pertenencias sin afectar a otras personas.', 'Las pertenencias sueltas pueden moverse durante una frenada. Los pasillos, puertas y sistemas de protección deben quedar libres. Convivir también significa respetar volumen, espacio y necesidades de movilidad.', ['Mochila en el pasillo', 'Una mochila bloquea parcialmente la salida.', 'Asegurarla en un lugar permitido sin bloquear puertas ni pasillos', 'Dejarla allí porque el viaje ya comenzó'], ['Persona necesita más tiempo', 'Una persona mayor tarda en ubicarse y el grupo muestra impaciencia.', 'Dar espacio y esperar a que quede estable antes de continuar', 'Presionarla para acelerar el abordaje'], 'Prepará una bolsa de viaje imaginaria y decidí dónde iría cada objeto para que no se convierta en obstáculo.'),
            $this->spec('PAS-PARADA', 'Una espera segura y visible', 'PASAJERO.TRANSPORTE', 'visual_exploration', 'Elegir dónde esperar sin invadir la calzada.', 'En la parada, mantenete sobre la acera o zona protegida y lejos del borde. Prepará el pasaje sin perder atención y no corras detrás del autobús. De noche, elegí un punto iluminado y visible.', ['Autobús aproximándose', 'Varias personas se acercan al borde antes de que el autobús se detenga.', 'Esperar atrás y acercarse ordenadamente cuando esté detenido', 'Bajar a la calzada para asegurar un lugar'], ['Parada oscura', 'La parada habitual tiene poca iluminación y casi no hay espacio protegido.', 'Buscar una alternativa más visible o acompañamiento apropiado', 'Usar la luz del teléfono desde el borde'], 'Dibujá una parada y marcá zona de espera, borde, trayectorias y punto de ascenso.'),
            $this->spec('PAS-ABORDAJE', 'Subir sin empujar ni quedar atrapado', 'PASAJERO.TRANSPORTE', 'guided_practice', 'Abordar después del descenso y conservar apoyos.', 'Primero se permite salir a quienes llegan. Luego se sube sin empujar, usando pasamanos cuando corresponde y cuidando el espacio entre vehículo y acera. Si las puertas cierran, no se corre ni se introducen objetos.', ['Personas descendiendo', 'El autobús abre y varias personas intentan salir.', 'Esperar a un lado y subir cuando la salida esté libre', 'Entrar primero para conseguir asiento'], ['Puertas cerrándose', 'Luna llega cuando la puerta empieza a cerrar.', 'Detenerse y esperar el siguiente servicio', 'Poner la mano o mochila para impedir el cierre'], 'Ensayá una fila, un espacio para descender y un ascenso sin contacto físico en un lugar fuera del tránsito.'),
            $this->spec('PAS-ESTABILIDAD', 'Estabilidad durante el recorrido', 'PASAJERO.TRANSPORTE', 'guided_practice', 'Usar asiento o apoyo y anticipar cambios de movimiento.', 'Un autobús puede frenar, girar o arrancar. Sentate cuando haya lugar; si viajás de pie, utilizá un apoyo firme y mantené pertenencias controladas. No cambiés de lugar mientras el vehículo se mueve sin necesidad.', ['Autobús inicia marcha', 'Luna sigue buscando algo en su mochila y no tiene apoyo.', 'Guardar la mochila, tomar un apoyo y estabilizarse', 'Caminar rápido hasta el fondo'], ['Asiento disponible', 'Hay un asiento prioritario libre y se acerca una persona que lo necesita.', 'Dejarlo disponible y buscar otro apoyo seguro', 'Ocuparlo porque llegó primero'], 'En casa, simulá una salida y frenada suaves con una señal verbal y practicá una postura estable sin empujones.'),
            $this->spec('PAS-PERTENENCIAS', 'Pertenencias bajo control', 'PASAJERO.CONVIVE', 'dilemma', 'Evitar que objetos bloqueen, distraigan o se conviertan en proyectiles.', 'Una frenada puede desplazar botellas, juguetes, bolsas o dispositivos. Guardalos antes de moverte. Nunca ocupés pasillos ni apoyos de otras personas, y no intentés recuperar un objeto mientras el vehículo está en movimiento.', ['Teléfono al suelo', 'El teléfono cae bajo otro asiento mientras el autobús circula.', 'Esperar a una detención segura y pedir ayuda para recuperarlo', 'Arrastrarse entre los asientos para buscarlo'], ['Bulto grande', 'Una bolsa no cabe sin bloquear parte del pasillo.', 'Reorganizarla en un lugar permitido o usar otra opción de transporte', 'Sostenerla en medio del paso'], 'Identificá en una imagen tres objetos que podrían moverse y proponé un lugar seguro para cada uno.'),
            $this->spec('PAS-PARADA-CAMBIA', 'Cuando la parada no es la esperada', 'PASAJERO.RESPONDE', 'dilemma', 'Pedir información y apoyo sin abandonar un lugar seguro.', 'Una ruta puede desviarse o pasar la parada. Si sos menor, permanecé con el personal responsable o dentro de un espacio público seguro, comunicá lo ocurrido y contactá a la persona acordada. No improvisés un recorrido aislado.', ['Parada equivocada', 'Una niña nota que el transporte pasó su parada habitual.', 'Avisar inmediatamente y seguir el plan familiar de apoyo', 'Bajarse en cualquier lugar y caminar sola'], ['Desvío por obra', 'El autobús anuncia una parada temporal en una zona desconocida.', 'Pedir información antes de bajar y elegir acompañamiento o conexión segura', 'Bajar y seguir a cualquier grupo'], 'Construí un plan sin datos privados: a quién avisar, qué información dar y dónde permanecer protegido.'),
            $this->spec('PAS-EMERGENCIA', 'Calma, indicaciones y ayuda', 'PASAJERO.RESPONDE', 'competency_challenge', 'Responder ante una detención inesperada o emergencia sin crear otro riesgo.', 'Ante humo, choque, falla o evacuación, escuchá indicaciones, dejá objetos y ayudá sin empujar. Una vez fuera, alejate del tránsito y reunite en el punto indicado. No regresés por pertenencias.', ['Humo durante el viaje', 'El vehículo se detiene y el personal indica evacuar.', 'Salir en orden, dejar objetos y dirigirse al punto seguro', 'Buscar primero la mochila'], ['Persona cae al frenar', 'Después de una frenada, una persona queda en el piso del autobús.', 'Avisar, dar espacio y seguir indicaciones sin moverla innecesariamente', 'Levantarla de inmediato entre varias personas'], 'Explicá la secuencia: reconocer, escuchar, salir o permanecer según indicación, ubicarse protegido y comunicar. Cerrá con una reflexión para el Pasaporte Vial.'),
        ];
    }

    /** @return array<string, mixed> */
    private function spec(string $code, string $title, string $indicator, string $experience, string $objective, string $text, array $first, array $second, string $practice): array
    {
        return compact('code', 'title', 'indicator', 'experience', 'objective', 'text', 'first', 'second', 'practice');
    }

    /** @param array<string, mixed> $spec */
    private function lesson(array $spec, int $position, string $competencyId): LessonInput
    {
        $blocks = [
            $this->text(1, 'Idea esencial', $spec['text']),
            $this->decision(2, $spec['first']),
            $this->decision(3, $spec['second']),
            $this->text(4, 'Práctica segura', $spec['practice']),
        ];
        $stage = 'pending_review';
        $indicator = $spec['indicator'];

        return new LessonInput($this->id($spec['code']), $spec['code'], $spec['title'], $spec['objective'], $position === 1 ? 17 : 18, $position, $blocks, LessonLearningDesign::fromArray([
            'stage' => $stage, 'jurisdictions' => ['GLOBAL', 'CR'], 'experience_type' => $spec['experience'],
            'behavior_objective' => $spec['objective'], 'competency_id' => $competencyId, 'subcompetency_code' => 'PASAJERO.RUTINA',
            'indicator_codes' => [$indicator],
            'evidence_rules' => [['indicator_code' => $indicator, 'event_type' => 'lesson_completed', 'minimum_observations' => 1, 'weight' => 1]],
            'requires_guardian' => true,
            'normative_sources' => [
                ['url' => 'https://pgrweb.go.cr/Scij/Busqueda/Normativa/Normas/nrm_texto_completo.aspx?nValor1=1&nValor2=73504&param1=NRM&strTipM=FN', 'reviewed_at' => '2026-09-13'],
                ['url' => 'https://www.csv.go.cr/seguridad-vial-virtual1', 'reviewed_at' => '2026-09-13'],
            ],
            'version' => 1,
        ]));
    }

    private function decision(int $position, array $data): ContentBlockInput
    {
        [$title, $context, $safe, $unsafe] = $data;

        return new ContentBlockInput((string) Str::uuid(), 'scenario', $position, [
            'title' => $title, 'context' => $context, 'prompt' => '¿Cuál decisión conserva mayor protección?',
            'accessible_text' => $context.' Elegí la alternativa que conserve protección, información y margen.',
            'choices' => [
                ['id' => 'impulso', 'label' => $unsafe, 'feedback' => 'Esta opción reduce protección o depende de que nada cambie.', 'correct' => false],
                ['id' => 'segura', 'label' => $safe, 'feedback' => 'Correcto. La decisión conserva protección y permite responder si la situación cambia.', 'correct' => true],
                ['id' => 'copiar', 'label' => 'Hacer lo mismo que otras personas sin volver a comprobar', 'feedback' => 'La conducta de otras personas no sustituye tu propia comprobación ni el apoyo apropiado.', 'correct' => false],
            ],
        ]);
    }

    private function text(int $position, string $title, string $markdown): ContentBlockInput
    {
        return new ContentBlockInput((string) Str::uuid(), 'text', $position, compact('title', 'markdown'));
    }

    private function id(string $suffix): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_DNS, 'edudrive.'.self::COURSE_CODE.'.'.$suffix)->toString();
    }
}
