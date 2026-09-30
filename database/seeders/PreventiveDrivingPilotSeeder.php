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

final class PreventiveDrivingPilotSeeder extends Seeder
{
    private const string COURSE_CODE = 'EDU-EXP-005';

    private const string COMPETENCY_CODE = 'CONDUCCION-PREVENTIVA';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || DB::table('academic_courses')->where('code', self::COURSE_CODE)->exists()) {
            return;
        }

        $competencyId = $this->competencyId();
        $course = app(CreateCourseHandler::class)->handle(new CreateCourseCommand(
            code: self::COURSE_CODE,
            title: 'Misión Conducción Preventiva',
            description: 'Educación para anticipar, conservar margen y proteger a todas las personas antes y durante un desplazamiento en vehículo.',
            objectives: 'Preparar persona y vehículo; gestionar velocidad y distancia; proteger usuarios vulnerables; detener o cambiar el plan ante distracciones, fatiga, clima o fallas.',
            prerequisites: 'No prepara para pruebas de licencia ni autoriza a conducir. Las personas sin habilitación realizan únicamente observación, análisis y prácticas fuera de vehículos en movimiento.',
            modality: 'virtual',
            durationHours: 3,
        ));

        $moduleIds = [$this->id('MOD-MARGEN'), $this->id('MOD-CUIDADO')];
        $unitIds = [$this->id('UNI-PREPARA'), $this->id('UNI-ESPACIO'), $this->id('UNI-COMPARTE'), $this->id('UNI-ADAPTA')];
        app(ReplaceCourseCurriculumHandler::class)->handle(new ReplaceCourseCurriculumCommand($course->id, [
            new CourseModuleInput($moduleIds[0], 'MISION-CONDUCE-MARGEN', 'Misión 1: El margen se prepara', 'La conducción preventiva comienza antes de mover el vehículo y continúa con espacio para responder.', 'Comprobar disposición y vehículo; ajustar velocidad y distancia a información real.', 70, 1, [], [
                new CourseUnitInput($unitIds[0], 'CONDUCE-PREPARA', '1. Antes de iniciar', 'Persona, vehículo y recorrido forman un solo sistema.', 'Reconocer señales que permiten continuar, corregir o posponer.', 35, 1, []),
                new CourseUnitInput($unitIds[1], 'CONDUCE-ESPACIO', '2. Tiempo y espacio para responder', 'Velocidad y distancia se adaptan al entorno.', 'Elegir un margen que tolere cambios y errores.', 35, 2, [$unitIds[0]]),
            ]),
            new CourseModuleInput($moduleIds[1], 'MISION-CONDUCE-CUIDA', 'Misión 2: Compartí y adaptá', 'La vía se comparte con personas de capacidades y velocidades diferentes.', 'Anticipar usuarios vulnerables y reconstruir el plan ante estados o condiciones adversas.', 70, 2, [$moduleIds[0]], [
                new CourseUnitInput($unitIds[2], 'CONDUCE-COMPARTE', '1. La vida alrededor del vehículo', 'Detectá peatones, bicicletas, motocicletas y puntos ciegos.', 'Reducir conflictos mediante observación, distancia y paciencia.', 35, 1, []),
                new CourseUnitInput($unitIds[3], 'CONDUCE-ADAPTA', '2. Saber cuándo no continuar', 'Fatiga, emoción, lluvia y fallas cambian la capacidad.', 'Detener o modificar el viaje antes de perder control.', 35, 2, [$unitIds[2]]),
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
        $competency = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand(self::COMPETENCY_CODE, 'Conduce o analiza la conducción con criterio preventivo', 'Prepara, anticipa y adapta decisiones para proteger a todas las personas.', 'risk_management', 'foundation'));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($competency->id, 'CONDUCE.PREVIENE', 'Conserva margen y adapta el viaje ante riesgos previsibles'));
        foreach ([
            ['CONDUCE.PREPARA', 'Comprueba estado personal, vehículo y recorrido antes de iniciar.'],
            ['CONDUCE.MARGEN', 'Adapta velocidad y distancia para conservar tiempo de respuesta.'],
            ['CONDUCE.COMPARTE', 'Anticipa y protege a usuarios vulnerables.'],
            ['CONDUCE.ADAPTA', 'Detiene o cambia el viaje ante capacidad o condiciones reducidas.'],
        ] as [$code, $description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($competency->id, 'CONDUCE.PREVIENE', $code, $description));
        }

        return $competency->id;
    }

    /** @return list<array<string, mixed>> */
    private function specs(): array
    {
        return [
            $this->spec('COND-ESTADO', 'La persona también se revisa', 'CONDUCE.PREPARA', 'guided_practice', 'Reconocer estados que requieren pausa, apoyo o cambio de conductor.', 'Prisa, enojo, sueño, dolor o sustancias que afectan la capacidad pueden reducir atención y juicio. La revisión previa incluye preguntarse si se puede observar, decidir y reaccionar de manera sostenida.', ['Sueño antes de salir', 'La persona bosteza, perdió horas de sueño y debe hacer un recorrido conocido.', 'Posponer, descansar o buscar otra persona o transporte', 'Salir porque conoce bien la ruta'], ['Discusión intensa', 'La persona sigue alterada y quiere conducir para despejarse.', 'Regularse fuera del vehículo y reevaluar antes de iniciar', 'Conducir rápido para terminar pronto'], 'Construí un semáforo personal: verde para continuar, amarillo para pausar y rojo para no iniciar. Las personas menores analizan ejemplos con una persona adulta.'),
            $this->spec('COND-VEHICULO', 'Información básica antes de mover', 'CONDUCE.PREPARA', 'visual_exploration', 'Detectar condiciones visibles que requieren corrección profesional.', 'Llantas, luces, cristales, espejos, frenos y objetos sueltos influyen en control y visión. La revisión no reemplaza mantenimiento profesional; permite detener una salida cuando algo no está listo.', ['Testigo desconocido', 'Al encender el vehículo aparece una advertencia que la persona no comprende.', 'Consultar información confiable y resolver antes de circular', 'Ignorarla si el vehículo parece funcionar'], ['Objeto junto a pedales', 'Una botella puede rodar hacia los pedales.', 'Retirarla y asegurar objetos antes de iniciar', 'Moverla con el pie si llega a estorbar'], 'Con el vehículo estacionado y apagado, una persona habilitada señala campos de visión y elementos visibles; nadie manipula controles sin autorización.'),
            $this->spec('COND-VELOCIDAD', 'Velocidad que permite comprender', 'CONDUCE.MARGEN', 'web_simulation', 'Adaptar velocidad a visibilidad, superficie y complejidad.', 'Una velocidad puede estar dentro de un límite y aun ser excesiva para lluvia, oscuridad, curvas, presencia escolar o información incompleta. La velocidad preventiva permite detenerse dentro del espacio visible.', ['Neblina localizada', 'La visibilidad disminuye de repente aunque la carretera esté conocida.', 'Reducir progresivamente y aumentar margen según lo visible', 'Mantener el ritmo porque conoce las curvas'], ['Zona escolar activa', 'Hay niñas, niños y vehículos que ocultan parte de la acera.', 'Reducir y preparar una detención ante apariciones posibles', 'Continuar si nadie está cruzando todavía'], 'Con objetos en una mesa, compará cuánto avanza una figura antes de detenerse a dos velocidades. No se practica conducción ni frenado real.'),
            $this->spec('COND-DISTANCIA', 'Distancia para el error ajeno', 'CONDUCE.MARGEN', 'dilemma', 'Conservar espacio frontal y lateral sin competir.', 'La distancia permite percibir, decidir y actuar. Se aumenta con lluvia, velocidad, oscuridad, carga o fatiga. También se deja espacio lateral a personas cuya trayectoria puede variar.', ['Vehículo muy cerca detrás', 'Otro vehículo presiona y hace señales para que aumente la velocidad.', 'Mantener control y facilitar el paso solo en un lugar seguro', 'Acelerar para evitar el conflicto'], ['Fila que frena adelante', 'Se encienden luces de freno varios vehículos más adelante.', 'Reducir temprano y ampliar espacio antes de la fila', 'Esperar a que frene el vehículo inmediato'], 'En una ilustración, marcá espacio frontal, lateral y ruta de salida. Explicá qué condición haría aumentar cada uno.'),
            $this->spec('COND-PEATON-CICLO', 'Personas que no llevan carrocería', 'CONDUCE.COMPARTE', 'visual_exploration', 'Anticipar movimientos de peatones y ciclistas sin exigirles perfección.', 'Niñas, personas mayores, peatones y ciclistas pueden necesitar más tiempo, cambiar de trayectoria o quedar ocultos. La responsabilidad preventiva es reducir antes, dejar espacio y evitar usar el vehículo para presionar.', ['Pelota cerca de la calle', 'Una pelota aparece entre vehículos estacionados y no se ve a ninguna persona.', 'Reducir y preparar una detención ante alguien que podría aparecer', 'Continuar porque la pelota está quieta'], ['Ciclista evita un hueco', 'Una bicicleta se acerca a una irregularidad que podría cambiar su trayectoria.', 'Reducir y aumentar distancia lateral sin adelantar en el conflicto', 'Pasar antes de que se desvíe'], 'Observá una escena dibujada y señalá quién puede estar oculto, quién necesita más tiempo y qué acción del vehículo crea protección.'),
            $this->spec('COND-MOTO-GRANDES', 'Motocicletas y vehículos grandes', 'CONDUCE.COMPARTE', 'dilemma', 'Reconocer puntos ciegos y diferencias de trayectoria.', 'Una motocicleta puede quedar oculta y un vehículo grande necesita más espacio para girar. Espejos y sensores ayudan, pero no eliminan puntos ciegos. La comprobación y la distancia siguen siendo necesarias.', ['Cambio de carril', 'Una motocicleta desaparece del espejo lateral antes del cambio.', 'Mantener posición y volver a comprobar el punto ciego', 'Cambiar porque ya no aparece en el espejo'], ['Camión girando', 'Un camión abre su trayectoria antes de una esquina.', 'Mantener distancia y no entrar al espacio interior del giro', 'Aprovechar el espacio que se abrió junto a la acera'], 'Con una maqueta, mové figuras por zonas visibles y ocultas. La meta es comprender límites de visión, no memorizar un dibujo único.'),
            $this->spec('COND-DISTRACCION', 'La tarea de conducir no se comparte', 'CONDUCE.ADAPTA', 'dilemma', 'Separar teléfono, navegación y conflictos de la conducción.', 'Leer, escribir, buscar una dirección o discutir ocupa atención aunque el vehículo avance despacio. La tarea adicional se resuelve antes de iniciar o después de detenerse en un lugar permitido y protegido.', ['Cambio de destino', 'La navegación pide una acción confusa en una zona de tránsito complejo.', 'Continuar con seguridad y detenerse en un lugar apropiado para revisar', 'Mirar la pantalla y cambiar de dirección de inmediato'], ['Mensaje urgente', 'Llega un mensaje que parece importante durante el recorrido.', 'No manipular el teléfono y detenerse de forma segura si debe atenderse', 'Leerlo en un semáforo'], 'Diseñá un protocolo familiar: configurar antes, guardar durante y detenerse de manera segura si una comunicación no puede esperar.'),
            $this->spec('COND-CLIMA-FALLA', 'Cuando continuar deja de ser responsable', 'CONDUCE.ADAPTA', 'competency_challenge', 'Detener o reconstruir el viaje ante clima, fatiga o falla.', 'La conducción preventiva no promete completar todo viaje. Un aguacero extremo, sueño, pérdida de visibilidad o una falla pueden exigir salir del flujo, detenerse y pedir ayuda. Continuar lentamente no siempre es suficiente.', ['Aguacero extremo', 'El agua reduce casi por completo la visión y comienza a acumularse.', 'Buscar un lugar seguro fuera del flujo y esperar o cambiar el plan', 'Seguir las luces del vehículo de adelante'], ['Respuesta extraña del freno', 'El pedal o control de frenado responde de forma diferente.', 'Reducir progresivamente, salir del flujo y solicitar asistencia', 'Probar varias frenadas mientras continúa'], 'Construí un plan B con señales para detenerse, lugar protegido, forma de pedir ayuda y reflexión final para el Pasaporte Vial.'),
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

        return new LessonInput($this->id($spec['code']), $spec['code'], $spec['title'], $spec['objective'], $position === 1 ? 17 : 18, $position, [$this->text(1, 'Idea esencial', $spec['text']), $this->decision(2, $spec['first']), $this->decision(3, $spec['second']), $this->text(4, 'Práctica sin conducción', $spec['practice'])], LessonLearningDesign::fromArray([
            'stage' => $stage, 'jurisdictions' => ['GLOBAL', 'CR'], 'experience_type' => $spec['experience'], 'behavior_objective' => $spec['objective'], 'competency_id' => $competencyId, 'subcompetency_code' => 'CONDUCE.PREVIENE', 'indicator_codes' => [$indicator],
            'evidence_rules' => [['indicator_code' => $indicator, 'event_type' => 'lesson_completed', 'minimum_observations' => 1, 'weight' => 1]], 'requires_guardian' => true,
            'normative_sources' => [['url' => 'https://pgrweb.go.cr/Scij/Busqueda/Normativa/Normas/nrm_texto_completo.aspx?nValor1=1&nValor2=73504&param1=NRM&strTipM=FN', 'reviewed_at' => '2026-09-13'], ['url' => 'https://www.csv.go.cr/seguridad-vial-virtual1', 'reviewed_at' => '2026-09-13']], 'version' => 1,
        ]));
    }

    private function decision(int $position, array $data): ContentBlockInput
    {
        [$title, $context, $safe, $unsafe] = $data;

        return new ContentBlockInput((string) Str::uuid(), 'scenario', $position, ['title' => $title, 'context' => $context, 'prompt' => '¿Qué decisión protege mejor la vida?', 'accessible_text' => $context.' Compará información, tiempo, espacio y posibilidad de detener el plan.', 'choices' => [
            ['id' => 'reaccion', 'label' => $unsafe, 'feedback' => 'Esta respuesta depende de que la situación no empeore y reduce el margen disponible.', 'correct' => false],
            ['id' => 'prevencion', 'label' => $safe, 'feedback' => 'Correcto. La decisión actúa antes del conflicto y conserva una salida.', 'correct' => true],
            ['id' => 'copiar', 'label' => 'Hacer lo que haga el vehículo de adelante', 'feedback' => 'Otra persona puede tener información, capacidad o destino diferentes.', 'correct' => false],
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
