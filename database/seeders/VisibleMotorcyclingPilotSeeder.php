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

final class VisibleMotorcyclingPilotSeeder extends Seeder
{
    private const string COURSE_CODE = 'EDU-EXP-004';

    private const string COMPETENCY_CODE = 'MOTO-VISIBLE-PREVENTIVA';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || DB::table('academic_courses')->where('code', self::COURSE_CODE)->exists()) {
            return;
        }

        $competencyId = $this->competencyId();
        $course = app(CreateCourseHandler::class)->handle(new CreateCourseCommand(
            code: self::COURSE_CODE,
            title: 'Misión Motociclista Visible',
            description: 'Una experiencia preventiva para preparar equipo y motocicleta, hacerse visible y conservar margen ante tránsito, clima y superficies cambiantes.',
            objectives: 'Comprobar equipo y vehículo; elegir posición visible; anticipar puntos ciegos; adaptar velocidad y detener el recorrido cuando cambia la seguridad.',
            prerequisites: 'No sustituye formación práctica ni habilita para conducir. Las personas menores recorren el contenido como pasajeras y usuarias que aprenden convivencia vial.',
            modality: 'virtual',
            durationHours: 3,
        ));

        $moduleIds = [$this->id('MOD-PREPARA'), $this->id('MOD-MARGEN')];
        $unitIds = [$this->id('UNI-EQUIPO'), $this->id('UNI-VISIBLE'), $this->id('UNI-ANTICIPA'), $this->id('UNI-ADAPTA')];
        app(ReplaceCourseCurriculumHandler::class)->handle(new ReplaceCourseCurriculumCommand($course->id, [
            new CourseModuleInput($moduleIds[0], 'MISION-MOTO-PREPARA', 'Misión 1: Prepará antes de rodar', 'Equipo, motocicleta y presencia visible se comprueban antes del movimiento.', 'Reconocer cuándo continuar, corregir o cancelar una salida.', 70, 1, [], [
                new CourseUnitInput($unitIds[0], 'MOTO-EQUIPO', '1. Persona y motocicleta listas', 'Revisá protección y controles esenciales.', 'Comprobar casco, protección, llantas, luces, frenos y fluidos sin improvisar reparaciones.', 35, 1, []),
                new CourseUnitInput($unitIds[1], 'MOTO-VISIBLE', '2. Ver y ser detectado', 'Usá luz, posición y comunicación.', 'Evitar puntos ocultos y comunicar movimientos previsibles.', 35, 2, [$unitIds[0]]),
            ]),
            new CourseModuleInput($moduleIds[1], 'MISION-MOTO-MARGEN', 'Misión 2: Conservá opciones', 'Anticipación, velocidad y adaptación para no quedar atrapado en un peligro.', 'Reconocer conflictos y reconstruir el plan antes de perder adherencia, espacio o visibilidad.', 70, 2, [$moduleIds[0]], [
                new CourseUnitInput($unitIds[2], 'MOTO-ANTICIPA', '1. Leé movimientos posibles', 'Detectá puertas, giros e intersecciones.', 'Preparar velocidad y posición antes del conflicto.', 35, 1, []),
                new CourseUnitInput($unitIds[3], 'MOTO-ADAPTA', '2. Clima, superficie y plan B', 'Cambiá el recorrido cuando cambia el margen.', 'Responder a lluvia, fatiga, fallas y obstáculos sin maniobras impulsivas.', 35, 2, [$unitIds[2]]),
            ]),
        ]));

        $specs = $this->specs();
        foreach ($unitIds as $index => $unitId) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($course->id, $unitId, [
                $this->lesson($specs[$index * 2], 1, $competencyId),
                $this->lesson($specs[$index * 2 + 1], 2, $competencyId),
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
            self::COMPETENCY_CODE, 'Se moviliza en motocicleta de manera visible y preventiva',
            'Prepara equipo y vehículo, anticipa conflictos y adapta el recorrido conservando margen.', 'vulnerable_road_users', 'foundation',
        ));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($competency->id, 'MOTO.PREVENCION', 'Aplica una secuencia preventiva antes y durante el recorrido'));
        foreach ([
            ['MOTO.REVISA', 'Comprueba protección y condición básica antes de salir.'],
            ['MOTO.VISIBLE', 'Elige posición y comunicación que favorecen su detección.'],
            ['MOTO.ANTICIPA', 'Reconoce trayectorias y puntos ciegos antes del conflicto.'],
            ['MOTO.ADAPTA', 'Ajusta o cancela el recorrido cuando disminuye el margen.'],
        ] as [$code, $description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($competency->id, 'MOTO.PREVENCION', $code, $description));
        }

        return $competency->id;
    }

    /** @return list<array<string, mixed>> */
    private function specs(): array
    {
        return [
            $this->spec('MOTO-CASCO', 'Casco que protege de verdad', 'MOTO.REVISA', 'guided_practice', 'Comprobar talla, ajuste, cierre y estado del casco.', 'El casco debe corresponder a la persona, quedar firme y abrochado. Un golpe, grieta, pieza faltante o modificación puede reducir su protección. La persona pasajera necesita protección adecuada igual que quien conduce.', ['Casco sin abrochar', 'El casco es de la talla correcta, pero la correa queda abierta para mayor comodidad.', 'Ajustar y abrochar antes de iniciar', 'Sostenerlo con la mano cuando aumente la velocidad'], ['Casco después de un impacto', 'El casco golpeó el suelo con fuerza y muestra una marca pequeña.', 'Retirarlo de uso y seguir las indicaciones del fabricante', 'Continuar porque el daño casi no se ve'], 'Fuera del tránsito, revisá talla, nivel, movimiento, correa y estado. Una persona competente confirma cualquier duda.'),
            $this->spec('MOTO-REVISION', 'La revisión detiene salidas inseguras', 'MOTO.REVISA', 'guided_practice', 'Detectar fallas básicas antes de poner la motocicleta en movimiento.', 'Llantas, luces, direccionales, frenos, espejos y señales de fuga se revisan antes de salir. Detectar una falla no significa saber repararla: significa evitar que llegue a la vía y buscar servicio competente.', ['Luz trasera apagada', 'La motocicleta funciona, pero la luz trasera no enciende.', 'No salir hasta corregir y comprobar la iluminación', 'Salir de día porque la luz parece menos necesaria'], ['Mancha bajo la motocicleta', 'Aparece una mancha nueva y no se conoce su origen.', 'No iniciar y solicitar una revisión competente', 'Limpiarla y comprobar durante el recorrido'], 'Construí una lista visual de revisión. No encendás ni manipulés una motocicleta sin autorización, formación y condiciones seguras.'),
            $this->spec('MOTO-POSICION', 'Una posición que produce información', 'MOTO.VISIBLE', 'visual_exploration', 'Reconocer posiciones visibles y con salida.', 'Ser visible no depende solo del color. La posición debe permitir ver, ser detectado y conservar espacio. Permanecer junto a puertas, entre vehículos o al costado de unidades grandes reduce información y opciones.', ['Junto a un autobús', 'Una motocicleta queda al costado de un autobús antes de una esquina.', 'Reducir y ubicarse detrás con distancia antes del giro', 'Acelerar para superar el punto ciego'], ['Entre filas detenidas', 'Los vehículos están detenidos, pero hay puertas y cruces ocultos.', 'Evitar avanzar por un espacio cuya salida y movimientos no se ven', 'Continuar porque los vehículos no avanzan'], 'Con una maqueta, ubicá motocicleta, automóvil y autobús. Marcá zonas visibles, puntos ciegos y una salida segura.'),
            $this->spec('MOTO-COMUNICA', 'Comunicar sin confiarse', 'MOTO.VISIBLE', 'dilemma', 'Usar luces, direccionales y trayectoria predecible sin asumir detección.', 'La direccional comunica intención, pero no reserva espacio. Se activa con anticipación, se comprueba el entorno y se cancela después de la maniobra. Una trayectoria estable ayuda a que otras personas comprendan el movimiento.', ['Direccional olvidada', 'La direccional continúa encendida después de un giro.', 'Cancelarla cuando sea seguro y reconstruir la información del entorno', 'Mantenerla porque las demás personas entenderán el error'], ['Cambio de carril', 'La direccional está activa, pero un vehículo permanece en la zona lateral.', 'Mantener posición hasta comprobar que existe espacio y detección', 'Cambiar porque la señal concede prioridad'], 'En una escena dibujada, ordená: observar, comunicar, volver a comprobar, ejecutar con margen y cancelar la señal.'),
            $this->spec('MOTO-INTERSECCION', 'Llegar preparado al conflicto', 'MOTO.ANTICIPA', 'web_simulation', 'Reducir y anticipar giros antes de entrar a una intersección.', 'Las intersecciones mezclan giros, cruces y diferencias de velocidad. Las ruedas, la posición y la reducción de otros vehículos aportan pistas, pero ninguna garantiza la trayectoria. Prepará una detención antes de entrar.', ['Vehículo sin direccional', 'Un automóvil reduce y sus ruedas comienzan a orientarse hacia el cruce.', 'Anticipar el giro y conservar distancia fuera de su trayectoria', 'Suponer que seguirá recto porque no señaló'], ['Luz favorable', 'El semáforo favorece la marcha, pero la intersección todavía está ocupada.', 'Esperar fuera hasta tener una salida visible y libre', 'Entrar para no perder el turno'], 'Usá fotografías o una maqueta para proponer dos movimientos posibles y una respuesta segura para ambos.'),
            $this->spec('MOTO-DISTANCIA', 'Espacio para detener y escapar', 'MOTO.ANTICIPA', 'dilemma', 'Conservar una distancia que tolere frenadas y errores.', 'La distancia necesaria cambia con velocidad, lluvia, superficie, carga, visibilidad y experiencia. Una separación segura deja tiempo para observar y frenar de forma progresiva, además de una ruta de salida.', ['Vehículo muy cerca detrás', 'Otro vehículo sigue a poca distancia y presiona para aumentar la velocidad.', 'Conservar control y facilitar una salida segura cuando sea posible', 'Acelerar por encima del margen propio'], ['Fila que frena', 'Las luces de freno aparecen varios vehículos adelante.', 'Reducir progresivamente y aumentar el espacio antes de la detención', 'Esperar a que frene el vehículo inmediato'], 'Compará en una simulación cómo lluvia y velocidad reducen el tiempo disponible. No practiqués frenadas en tránsito real.'),
            $this->spec('MOTO-LLUVIA', 'Lluvia: menos adherencia y menos visión', 'MOTO.ADAPTA', 'web_simulation', 'Modificar velocidad, distancia y recorrido ante lluvia.', 'Las primeras gotas pueden mezclarse con residuos; pintura, metal, hojas y agua acumulada cambian la adherencia. La lluvia también reduce visibilidad propia y ajena. La respuesta puede ser reducir, esperar o cancelar.', ['Aguacero repentino', 'La lluvia intensa impide ver con claridad y empieza a acumular agua.', 'Salir del flujo en un lugar seguro y reevaluar o esperar', 'Acelerar para abandonar pronto la zona'], ['Pintura vial mojada', 'La trayectoria cruza una marca pintada mientras la motocicleta necesita girar.', 'Reducir antes y evitar maniobras bruscas sobre la superficie', 'Frenar con fuerza mientras gira sobre la marca'], 'Clasificá superficies en estable, variable o no transitable y explicá qué cambio activa cada categoría.'),
            $this->spec('MOTO-PLAN-B', 'Fatiga, falla y plan B', 'MOTO.ADAPTA', 'competency_challenge', 'Detener o cambiar el recorrido cuando disminuye la capacidad o el vehículo falla.', 'Fatiga, emoción intensa, pasajero mal ubicado o una falla cambian el riesgo. Continuar despacio no siempre resuelve el problema. Una decisión experta reconoce la señal, sale del flujo y consigue ayuda sin improvisar.', ['Cansancio evidente', 'La persona pierde concentración y no recuerda parte del último tramo.', 'Detenerse en un lugar seguro y no continuar hasta recuperar capacidad', 'Abrir la ventilación del casco y esforzarse más'], ['Freno irregular', 'Durante el recorrido un freno empieza a responder de manera distinta.', 'Salir del flujo de forma progresiva y no continuar rodando', 'Usar solo el otro freno hasta llegar'], 'Creá un protocolo: señal de alerta, lugar para detenerse, apoyo disponible y criterio para no continuar. Registrá la reflexión en tu Pasaporte Vial.'),
        ];
    }

    private function spec(string $code, string $title, string $indicator, string $experience, string $objective, string $text, array $first, array $second, string $practice): array
    {
        return compact('code', 'title', 'indicator', 'experience', 'objective', 'text', 'first', 'second', 'practice');
    }

    private function lesson(array $spec, int $position, string $competencyId): LessonInput
    {
        $indicator = $spec['indicator'];
        $stage = 'pending_review';

        return new LessonInput($this->id($spec['code']), $spec['code'], $spec['title'], $spec['objective'], $position === 1 ? 17 : 18, $position, [
            $this->text(1, 'Idea esencial', $spec['text']), $this->decision(2, $spec['first']), $this->decision(3, $spec['second']), $this->text(4, 'Práctica protegida', $spec['practice']),
        ], LessonLearningDesign::fromArray([
            'stage' => $stage, 'jurisdictions' => ['GLOBAL', 'CR'], 'experience_type' => $spec['experience'], 'behavior_objective' => $spec['objective'],
            'competency_id' => $competencyId, 'subcompetency_code' => 'MOTO.PREVENCION', 'indicator_codes' => [$indicator],
            'evidence_rules' => [['indicator_code' => $indicator, 'event_type' => 'lesson_completed', 'minimum_observations' => 1, 'weight' => 1]],
            'requires_guardian' => true,
            'normative_sources' => [['url' => 'https://pgrweb.go.cr/Scij/Busqueda/Normativa/Normas/nrm_texto_completo.aspx?nValor1=1&nValor2=73504&param1=NRM&strTipM=FN', 'reviewed_at' => '2026-09-13'], ['url' => 'https://www.csv.go.cr/seguridad-vial-virtual1', 'reviewed_at' => '2026-09-13']],
            'version' => 1,
        ]));
    }

    private function decision(int $position, array $data): ContentBlockInput
    {
        [$title, $context, $safe, $unsafe] = $data;

        return new ContentBlockInput((string) Str::uuid(), 'scenario', $position, ['title' => $title, 'context' => $context, 'prompt' => '¿Cuál respuesta conserva mayor margen?', 'accessible_text' => $context.' Compará protección, información y espacio antes de decidir.', 'choices' => [
            ['id' => 'exponer', 'label' => $unsafe, 'feedback' => 'Esta opción reduce información, control o espacio para responder.', 'correct' => false],
            ['id' => 'margen', 'label' => $safe, 'feedback' => 'Correcto. La decisión conserva margen y evita depender de que todo salga perfecto.', 'correct' => true],
            ['id' => 'copiar', 'label' => 'Copiar lo que haga otra persona sin comprobar', 'feedback' => 'Otra persona puede tener capacidades, información o riesgos diferentes.', 'correct' => false],
        ]]);
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
