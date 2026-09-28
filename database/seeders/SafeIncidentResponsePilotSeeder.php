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

final class SafeIncidentResponsePilotSeeder extends Seeder
{
    private const string COURSE_CODE = 'EDU-EXP-008';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || DB::table('academic_courses')->where('code', self::COURSE_CODE)->exists()) {
            return;
        }
        $competencyId = $this->competency();
        $course = app(CreateCourseHandler::class)->handle(new CreateCourseCommand(code: self::COURSE_CODE, title: 'Misión Respuesta Segura ante Incidentes', description: 'Aprende a protegerte, alertar y colaborar sin convertirte en otra persona afectada.', objectives: 'Reconocer peligros activos; pedir ayuda con información útil; seguir indicaciones y cuidar la escena sin realizar intervenciones para las que no se tiene formación.', prerequisites: 'No sustituye primeros auxilios ni servicios de emergencia. Las personas menores practican únicamente mediante historias y simulaciones acompañadas.', modality: 'virtual', durationHours: 1));
        $moduleId = $this->id('MOD');
        $units = [$this->id('PROTEGE'), $this->id('ALERTA'), $this->id('COLABORA')];
        app(ReplaceCourseCurriculumHandler::class)->handle(new ReplaceCourseCurriculumCommand($course->id, [new CourseModuleInput($moduleId, 'MISION-INCIDENTE-SEGURO', 'Misión: Protegé, alertá y colaborá', 'Tres retos para responder sin aumentar el peligro.', 'Mantener seguridad personal, comunicar datos útiles y seguir instrucciones.', 54, 1, [], [
            new CourseUnitInput($units[0], 'INCIDENTE-PROTEGE', '1. Primero no te expongás', 'Detectá tránsito, fuego, electricidad y derrames.', 'Mantener distancia y buscar un lugar protegido.', 18, 1, []),
            new CourseUnitInput($units[1], 'INCIDENTE-ALERTA', '2. Pedir ayuda con claridad', 'Ordená la información esencial.', 'Comunicar qué ocurre y seguir preguntas del servicio de ayuda.', 18, 2, [$units[0]]),
            new CourseUnitInput($units[2], 'INCIDENTE-COLABORA', '3. Ayudar dentro de tus capacidades', 'Evitá rumores, aglomeración y movimientos innecesarios.', 'Seguir indicaciones y conservar libre el acceso.', 18, 3, [$units[1]]),
        ])]));
        foreach ($this->specs() as $i => $s) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($course->id, $units[$i], [$this->lesson($s, $competencyId)]));
        }
        app(SubmitCourseForReviewHandler::class)->handle(new SubmitCourseForReviewCommand($course->id));
        app(ApproveCourseHandler::class)->handle(new ApproveCourseCommand($course->id));
        app(PublishCourseHandler::class)->handle(new PublishCourseCommand($course->id));
    }

    private function competency(): string
    {
        $id = DB::table('academic_competencies')->where('code', 'RESPUESTA-INCIDENTE-SEGURA')->value('id');
        if ($id !== null) {
            return (string) $id;
        }
        $c = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand('RESPUESTA-INCIDENTE-SEGURA', 'Responde a un incidente vial sin aumentar el peligro', 'Se protege, alerta con claridad y colabora dentro de sus capacidades.', 'risk_management', 'foundation'));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($c->id, 'INCIDENTE.RESPONDE', 'Protege, alerta y colabora con seguridad'));
        foreach ([['INCIDENTE.PROTEGE', 'Reconoce peligros activos y mantiene distancia.'], ['INCIDENTE.ALERTA', 'Comunica información observable y útil.'], ['INCIDENTE.COLABORA', 'Sigue indicaciones y evita interferir.']] as [$code,$description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($c->id, 'INCIDENTE.RESPONDE', $code, $description));
        }

        return $c->id;
    }

    private function specs(): array
    {
        return [
            ['INC-PROTEGE', 'Una escena que todavía puede cambiar', 'INCIDENTE.PROTEGE', 'visual_exploration', 'Reconocer peligros antes de acercarse.', 'Después de un incidente puede seguir circulando tránsito, existir humo, cables, combustible u objetos inestables. Ayudar comienza por mantener distancia y evitar una segunda emergencia.', ['Bicicleta caída', 'Una persona cayó y varios vehículos siguen pasando cerca.', 'Ubicarse protegido y pedir que una persona adulta o servicio de ayuda intervenga', 'Correr hacia la calzada'], ['Cable desconocido', 'Un cable quedó sobre la vía después de un choque.', 'Mantener distancia y advertir sin tocarlo', 'Moverlo para despejar el paso'], 'En una ilustración, marcá zona de peligro, lugar protegido y acceso que debe quedar libre.'],
            ['INC-ALERTA', 'Información que ayuda', 'INCIDENTE.ALERTA', 'guided_practice', 'Comunicar hechos observables sin inventar.', 'Al pedir ayuda describí qué ocurrió, peligros visibles, cantidad aproximada de personas y una referencia pública del lugar. Respondé preguntas y no terminés la comunicación hasta recibir indicación. Las niñas y niños buscan primero una persona adulta segura cuando sea posible.', ['Mensaje incompleto', 'Una persona solo dice “hubo un accidente” y corta.', 'Permanecer disponible y responder las preguntas necesarias', 'Enviar el mensaje a un grupo y esperar'], ['Ubicación privada', 'Una niña necesita pedir ayuda desde un lugar público.', 'Dar al servicio de ayuda la referencia necesaria con apoyo adulto', 'Publicar ubicación y fotos en redes'], 'Practicá una llamada ficticia: saludo, hecho observable, peligro, referencia pública y escucha de instrucciones.'],
            ['INC-COLABORA', 'Ayudar sin invadir', 'INCIDENTE.COLABORA', 'competency_challenge', 'Seguir indicaciones y proteger privacidad y acceso.', 'No movás a una persona lesionada ni realicés técnicas para las que no tenés formación, salvo indicación directa de personal competente. Evitá fotos, rumores y aglomeraciones; dejá libres las rutas de emergencia.', ['Persona en el suelo', 'Alguien quiere levantar inmediatamente a la persona afectada.', 'Dar espacio, alertar y seguir instrucciones profesionales', 'Moverla para que el tránsito continúe'], ['Llegan emergencias', 'Un grupo bloquea el acceso mientras graba videos.', 'Alejarse, liberar el paso y proteger la privacidad', 'Acercarse para obtener información'], 'Explicá el protocolo proteger, alertar, escuchar y colaborar. Registrá qué límite personal te ayuda a no aumentar el riesgo en el Pasaporte Vial.'],
        ];
    }

    private function lesson(array $s, string $competencyId): LessonInput
    {
        [$code,$title,$indicator,$experience,$objective,$text,$a,$b,$practice] = $s;
        $stage = 'pending_review';

        return new LessonInput($this->id($code), $code, $title, $objective, 18, 1, [$this->text(1, 'Idea esencial', $text), $this->scenario(2, $a), $this->scenario(3, $b), $this->text(4, 'Práctica simulada', $practice)], LessonLearningDesign::fromArray(['stage' => $stage, 'jurisdictions' => ['GLOBAL', 'CR'], 'experience_type' => $experience, 'behavior_objective' => $objective, 'competency_id' => $competencyId, 'subcompetency_code' => 'INCIDENTE.RESPONDE', 'indicator_codes' => [$indicator], 'evidence_rules' => [['indicator_code' => $indicator, 'event_type' => 'lesson_completed', 'minimum_observations' => 1, 'weight' => 1]], 'requires_guardian' => true, 'normative_sources' => [['url' => 'https://www.csv.go.cr/seguridad-vial-virtual1', 'reviewed_at' => '2026-09-13']], 'version' => 1]));
    }

    private function scenario(int $p, array $d): ContentBlockInput
    {
        [$title,$context,$safe,$unsafe] = $d;

        return new ContentBlockInput((string) Str::uuid(), 'scenario', $p, ['title' => $title, 'context' => $context, 'prompt' => '¿Qué respuesta evita aumentar el peligro?', 'accessible_text' => $context.' Conservá seguridad personal, acceso y comunicación clara.', 'choices' => [['id' => 'exponer', 'label' => $unsafe, 'feedback' => 'Esta acción puede crear otra persona afectada o interferir con la ayuda.', 'correct' => false], ['id' => 'segura', 'label' => $safe, 'feedback' => 'Correcto. Protege, comunica y respeta los límites de la propia capacidad.', 'correct' => true], ['id' => 'grabar', 'label' => 'Grabar y compartir antes de pedir ayuda', 'feedback' => 'Difundir imágenes retrasa la respuesta y vulnera la privacidad.', 'correct' => false]]]);
    }

    private function text(int $p, string $title, string $markdown): ContentBlockInput
    {
        return new ContentBlockInput((string) Str::uuid(), 'text', $p, compact('title', 'markdown'));
    }

    private function id(string $s): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_DNS, 'edudrive.'.self::COURSE_CODE.'.'.$s)->toString();
    }
}
