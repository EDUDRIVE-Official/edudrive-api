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

final class SustainableMobilityPilotSeeder extends Seeder
{
    private const string COURSE_CODE = 'EDU-EXP-006';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || DB::table('academic_courses')->where('code', self::COURSE_CODE)->exists()) {
            return;
        }
        $competencyId = $this->competencyId();
        $course = app(CreateCourseHandler::class)->handle(new CreateCourseCommand(
            code: self::COURSE_CODE, title: 'Misión Movilidad Sostenible',
            description: 'Decisiones para movernos con seguridad usando menos energía, ruido, contaminación y espacio.',
            objectives: 'Comparar medios, combinar viajes, reducir impactos evitables y proponer movilidad segura e inclusiva.',
            prerequisites: 'No requiere vehículo. Las prácticas usan mapas, observación protegida y acuerdos familiares.', modality: 'virtual', durationHours: 2,
        ));
        $moduleId = $this->id('MOD-SOSTENIBLE');
        $unitIds = [$this->id('UNI-ELIGE'), $this->id('UNI-COMBINA'), $this->id('UNI-CUIDA')];
        app(ReplaceCourseCurriculumHandler::class)->handle(new ReplaceCourseCurriculumCommand($course->id, [
            new CourseModuleInput($moduleId, 'MISION-MOVILIDAD-SOSTENIBLE', 'Misión: Elegí, combiná y cuidá', 'Seis retos para relacionar movilidad, seguridad, inclusión y ambiente.', 'Construir decisiones sostenibles que nunca sacrifiquen la protección de las personas.', 108, 1, [], [
                new CourseUnitInput($unitIds[0], 'MOVILIDAD-ELIGE', '1. Cada viaje necesita una decisión', 'Compará propósito, distancia y personas.', 'Elegir un medio apropiado y accesible.', 36, 1, []),
                new CourseUnitInput($unitIds[1], 'MOVILIDAD-COMBINA', '2. Conexiones con margen', 'Combiná caminata, bicicleta y transporte.', 'Planificar esperas, transbordos y plan B.', 36, 2, [$unitIds[0]]),
                new CourseUnitInput($unitIds[2], 'MOVILIDAD-CUIDA', '3. El viaje deja una huella', 'Reducí energía, ruido y espacio ocupado.', 'Mejorar impactos sin trasladar riesgos.', 36, 3, [$unitIds[1]]),
            ]),
        ]));
        foreach ($this->specs() as $index => $pair) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($course->id, $unitIds[$index], [$this->lesson($pair[0], 1, $competencyId), $this->lesson($pair[1], 2, $competencyId)]));
        }
        app(SubmitCourseForReviewHandler::class)->handle(new SubmitCourseForReviewCommand($course->id));
        app(ApproveCourseHandler::class)->handle(new ApproveCourseCommand($course->id));
        app(PublishCourseHandler::class)->handle(new PublishCourseCommand($course->id));
    }

    private function competencyId(): string
    {
        $existing = DB::table('academic_competencies')->where('code', 'MOVILIDAD-SOSTENIBLE')->value('id');
        if ($existing !== null) {
            return (string) $existing;
        }
        $competency = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand('MOVILIDAD-SOSTENIBLE', 'Elige movilidad segura, inclusiva y sostenible', 'Compara medios y reduce impactos sin trasladar riesgos.', 'eco_driving', 'foundation'));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($competency->id, 'MOVILIDAD.DECIDE', 'Compara, conecta y mejora decisiones de movilidad'));
        foreach ([['MOVILIDAD.ELIGE', 'Compara medios según necesidad y accesibilidad.'], ['MOVILIDAD.CONECTA', 'Planifica conexiones con margen y alternativa.'], ['MOVILIDAD.CUIDA', 'Reduce impactos sobre personas y comunidad.']] as [$code, $description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($competency->id, 'MOVILIDAD.DECIDE', $code, $description));
        }

        return $competency->id;
    }

    private function specs(): array
    {
        return [
            [
                $this->spec('ECO-PROPOSITO', 'El propósito antes que la costumbre', 'MOVILIDAD.ELIGE', 'visual_exploration', 'Comparar propósito, distancia, carga, clima y personas.', 'No todos los viajes necesitan el mismo medio. Una opción sostenible debe ser segura, accesible y posible para todas las personas.', ['Compra cercana', 'Existe una ruta peatonal protegida para recoger un objeto pequeño.', 'Valorar caminar o combinar el encargo', 'Usar dos vehículos por costumbre'], ['Movilidad reducida', 'La opción de menor consumo no es accesible para alguien del grupo.', 'Elegir una alternativa accesible aunque cambie el medio', 'Separar al grupo y dejar que resuelva sola'], 'Compará tres viajes imaginarios sin registrar rutas personales.'),
                $this->spec('ECO-COORDINA', 'Coordinar también reduce viajes', 'MOVILIDAD.ELIGE', 'dilemma', 'Agrupar necesidades compatibles sin sacrificar cuidado.', 'Coordinar encargos o compartir un recorrido puede reducir desplazamientos. La eficiencia no justifica prisa, sobrecarga ni falta de acompañamiento.', ['Dos encargos próximos', 'Dos destinos cercanos tienen horarios compatibles.', 'Planificar un solo recorrido seguro', 'Realizar dos viajes sin comparar'], ['Acompañamiento necesario', 'Cancelar el viaje dejaría a una persona menor sin apoyo.', 'Realizar el viaje protegido; la seguridad tiene prioridad', 'Cancelar solo para reducir consumo'], 'Ordená actividades ficticias y agrupá únicamente las compatibles.'),
            ],
            [
                $this->spec('ECO-CONEXION', 'Conexiones que no obligan a correr', 'MOVILIDAD.CONECTA', 'web_simulation', 'Planificar transbordos con espera protegida.', 'Un viaje combinado necesita tiempo, un lugar seguro para esperar e información para continuar. Si exige correr, carece de margen.', ['Transbordo estrecho', 'La conexión solo funciona cruzando una avenida con prisa.', 'Elegir más margen u otra conexión', 'Mantenerla porque ahorra tiempo'], ['Último servicio', 'Perderlo dejaría al grupo en una parada aislada.', 'Preparar una alternativa antes de salir', 'Confiar en que llegará a tiempo'], 'Armá con tarjetas un viaje con conexión y plan B.'),
                $this->spec('ECO-ESCUELA', 'Llegar juntos sin trasladar el riesgo', 'MOVILIDAD.CONECTA', 'community_observation', 'Comparar movilidad escolar compartida y segura.', 'Caminar en grupo, bicicleta acompañada o transporte compartido pueden reducir vehículos, si respetan edades, capacidades y puntos protegidos.', ['Doble fila', 'Cada familia se detiene en doble fila frente a la escuela.', 'Organizar puntos permitidos y opciones compartidas', 'Subir a la acera para despejar'], ['Grupo caminante', 'Existe una ruta protegida para varias niñas y niños.', 'Definir acompañamiento y protocolo ante cambios', 'Permitir separarse sin avisar'], 'Dibujá una escuela imaginaria y distribuí llegadas sin cruces ocultos.'),
            ],
            [
                $this->spec('ECO-SUAVIDAD', 'Menos prisa, menos energía', 'MOVILIDAD.CUIDA', 'web_simulation', 'Relacionar anticipación con desplazamientos suaves.', 'Planificar evita vueltas y urgencia. Observar lejos permite acelerar y reducir progresivamente, pero ahorrar energía nunca significa ignorar el entorno.', ['Fila adelante', 'Las luces de freno aparecen varios vehículos más adelante.', 'Reducir temprano y conservar espacio', 'Acelerar hasta alcanzar la fila'], ['Bajada en bicicleta', 'La pendiente permite ganar mucha velocidad.', 'Controlar desde antes con velocidad manejable', 'Aprovechar todo el impulso'], 'Compará con una figura una secuencia brusca y otra anticipada.'),
                $this->spec('ECO-COMUNIDAD', 'Una movilidad que mejora la vida', 'MOVILIDAD.CUIDA', 'competency_challenge', 'Integrar seguridad, inclusión y reducción de impactos.', 'Ruido, emisiones y aceras bloqueadas afectan a personas que no participan del viaje. Ninguna intención ambiental justifica exposición o exclusión.', ['Espera frente a escuela', 'Un vehículo genera ruido y gases cerca de la entrada.', 'Usar un punto apropiado y reducir espera motorizada', 'Mantenerlo allí porque falta poco'], ['Rampa disponible', 'Hay espacio para estacionar, pero bloquearía una rampa.', 'Buscar otro lugar que conserve la ruta accesible', 'Ocuparla mientras nadie la usa'], 'Creá una propuesta con beneficio, barrera, alternativa y reflexión para el Pasaporte Vial.'),
            ],
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

        return new LessonInput($this->id($spec['code']), $spec['code'], $spec['title'], $spec['objective'], 18, $position, [$this->text(1, 'Idea esencial', $spec['text']), $this->decision(2, $spec['first']), $this->decision(3, $spec['second']), $this->text(4, 'Práctica segura', $spec['practice'])], LessonLearningDesign::fromArray(['stage' => $stage, 'jurisdictions' => ['GLOBAL', 'CR'], 'experience_type' => $spec['experience'], 'behavior_objective' => $spec['objective'], 'competency_id' => $competencyId, 'subcompetency_code' => 'MOVILIDAD.DECIDE', 'indicator_codes' => [$indicator], 'evidence_rules' => [['indicator_code' => $indicator, 'event_type' => 'lesson_completed', 'minimum_observations' => 1, 'weight' => 1]], 'requires_guardian' => true, 'normative_sources' => [['url' => 'https://www.csv.go.cr/seguridad-vial-virtual1', 'reviewed_at' => '2026-09-13']], 'version' => 1]));
    }

    private function decision(int $position, array $data): ContentBlockInput
    {
        [$title, $context, $safe, $unsafe] = $data;

        return new ContentBlockInput((string) Str::uuid(), 'scenario', $position, ['title' => $title, 'context' => $context, 'prompt' => '¿Qué opción equilibra seguridad y sostenibilidad?', 'accessible_text' => $context.' Compará seguridad, inclusión, impacto y alternativa.', 'choices' => [['id' => 'traslada', 'label' => $unsafe, 'feedback' => 'Esta opción traslada riesgo o impacto a otra persona.', 'correct' => false], ['id' => 'equilibra', 'label' => $safe, 'feedback' => 'Correcto. Protege a las personas y reduce un impacto evitable.', 'correct' => true], ['id' => 'mayoria', 'label' => 'Hacer lo que elija la mayoría sin comparar', 'feedback' => 'La popularidad no garantiza seguridad ni accesibilidad.', 'correct' => false]]]);
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
