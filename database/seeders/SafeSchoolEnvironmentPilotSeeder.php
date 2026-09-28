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

final class SafeSchoolEnvironmentPilotSeeder extends Seeder
{
    private const string COURSE_CODE = 'EDU-EXP-007';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || DB::table('academic_courses')->where('code', self::COURSE_CODE)->exists()) {
            return;
        }
        $competencyId = $this->competency();
        $course = app(CreateCourseHandler::class)->handle(new CreateCourseCommand(code: self::COURSE_CODE, title: 'Misión Entorno Escolar Seguro', description: 'Organiza llegadas, salidas y recorridos escolares protegiendo a toda la comunidad.', objectives: 'Reconocer conflictos, mantener rutas accesibles y coordinar cambios sin improvisar.', prerequisites: 'No requiere datos reales. Las observaciones de menores se realizan con acompañamiento adulto.', modality: 'virtual', durationHours: 1));
        $moduleId = $this->id('MOD');
        $units = [$this->id('LLEGADA'), $this->id('ESPACIO'), $this->id('ACUERDO')];
        app(ReplaceCourseCurriculumHandler::class)->handle(new ReplaceCourseCurriculumCommand($course->id, [new CourseModuleInput($moduleId, 'MISION-ESCUELA-SEGURA', 'Misión: Llegamos y salimos con cuidado', 'Tres retos para observar, organizar y mejorar un entorno escolar.', 'Reducir trayectorias ocultas, bloqueos y exposición mediante acuerdos.', 54, 1, [], [
            new CourseUnitInput($units[0], 'ESCUELA-LLEGADA', '1. Llegadas que no crean peligro', 'Leé trayectorias simultáneas.', 'Preparar la llegada antes del conflicto.', 18, 1, []),
            new CourseUnitInput($units[1], 'ESCUELA-ESPACIO', '2. Cada espacio tiene una función', 'Protegé cruces, aceras y rampas.', 'Evitar bloqueos y puntos ocultos.', 18, 2, [$units[0]]),
            new CourseUnitInput($units[2], 'ESCUELA-ACUERDO', '3. La comunidad se coordina', 'Convertí observaciones en acuerdos.', 'Responder a clima y obras con un plan.', 18, 3, [$units[1]]),
        ])]));
        foreach ($this->specs() as $i => $spec) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($course->id, $units[$i], [$this->lesson($spec, $competencyId)]));
        }
        app(SubmitCourseForReviewHandler::class)->handle(new SubmitCourseForReviewCommand($course->id));
        app(ApproveCourseHandler::class)->handle(new ApproveCourseCommand($course->id));
        app(PublishCourseHandler::class)->handle(new PublishCourseCommand($course->id));
    }

    private function competency(): string
    {
        $id = DB::table('academic_competencies')->where('code', 'ENTORNO-ESCOLAR-SEGURO')->value('id');
        if ($id !== null) {
            return (string) $id;
        }
        $c = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand('ENTORNO-ESCOLAR-SEGURO', 'Participa en un entorno escolar seguro', 'Observa conflictos y aplica acuerdos para proteger llegadas y salidas.', 'vulnerable_road_users', 'foundation'));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($c->id, 'ESCUELA.CUIDA', 'Observa, protege y coordina la movilidad escolar'));
        foreach ([['ESCUELA.OBSERVA', 'Reconoce trayectorias y conflictos.'], ['ESCUELA.PROTEGE', 'Mantiene libres espacios accesibles.'], ['ESCUELA.COORDINA', 'Aplica acuerdos ante cambios.']] as [$code,$description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($c->id, 'ESCUELA.CUIDA', $code, $description));
        }

        return $c->id;
    }

    private function specs(): array
    {
        return [
            ['ESC-LLEGADA', 'Radar de la entrada escolar', 'ESCUELA.OBSERVA', 'visual_exploration', 'Reconocer trayectorias y puntos ocultos.', 'En pocos minutos coinciden peatones, bicicletas, autobuses y vehículos. Observá puertas, giros y obstáculos antes de avanzar.', ['Autobús que oculta', 'Un autobús bloquea la visión del cruce.', 'Esperar protegido hasta recuperar visibilidad', 'Cruzar por delante porque está detenido'], ['Descenso en doble fila', 'La puerta abre hacia vehículos en movimiento.', 'Reubicar el vehículo antes de descender', 'Salir rápidamente antes de que pasen'], 'Dibujá una entrada ficticia y marcá trayectorias sin usar datos reales.'],
            ['ESC-ESPACIO', 'Cruces, aceras y rampas libres', 'ESCUELA.PROTEGE', 'community_observation', 'Evitar barreras y presión en espacios compartidos.', 'Vehículos, filas o ventas pueden ocultar niñas pequeñas y bloquear rutas. La llegada no debe trasladar riesgo a quien camina o usa apoyos.', ['Rampa ocupada', 'Un vehículo usa la rampa durante la salida.', 'Liberarla y buscar un espacio permitido', 'Mantenerlo hasta recoger al estudiante'], ['Grupo en la acera', 'Una fila ocupa todo el paso.', 'Reorganizarla dejando un corredor continuo', 'Pedir que rodeen por la calzada'], 'Auditá una imagen ficticia y proponé mejoras sin fotografiar personas.'],
            ['ESC-ACUERDO', 'Un plan que cambia con el entorno', 'ESCUELA.COORDINA', 'competency_challenge', 'Integrar observación, inclusión y plan alternativo.', 'Un acuerdo seguro define rutas, responsables y una alternativa para lluvia, obras o fallas. La meta es reducir conflictos, no buscar culpables.', ['Aguacero en la salida', 'Familias corren entre vehículos.', 'Esperar protegido y activar el punto alternativo', 'Acercar vehículos a cualquier espacio'], ['Obra inesperada', 'La entrada peatonal habitual queda bloqueada.', 'Comunicar y activar una ruta accesible', 'Permitir que cada familia improvise'], 'Creá un plan ficticio con peligro, acuerdo, responsable, plan B y reflexión para el Pasaporte Vial.'],
        ];
    }

    private function lesson(array $s, string $competencyId): LessonInput
    {
        [$code,$title,$indicator,$experience,$objective,$text,$a,$b,$practice] = $s;
        $stage = 'pending_review';

        return new LessonInput($this->id($code), $code, $title, $objective, 18, 1, [$this->text(1, 'Idea esencial', $text), $this->scenario(2, $a), $this->scenario(3, $b), $this->text(4, 'Práctica protegida', $practice)], LessonLearningDesign::fromArray(['stage' => $stage, 'jurisdictions' => ['GLOBAL', 'CR'], 'experience_type' => $experience, 'behavior_objective' => $objective, 'competency_id' => $competencyId, 'subcompetency_code' => 'ESCUELA.CUIDA', 'indicator_codes' => [$indicator], 'evidence_rules' => [['indicator_code' => $indicator, 'event_type' => 'lesson_completed', 'minimum_observations' => 1, 'weight' => 1]], 'requires_guardian' => true, 'normative_sources' => [['url' => 'https://mep.go.cr/programas-proyectos/camino-seguro', 'reviewed_at' => '2026-09-13']], 'version' => 1]));
    }

    private function scenario(int $position, array $d): ContentBlockInput
    {
        [$title,$context,$safe,$unsafe] = $d;

        return new ContentBlockInput((string) Str::uuid(), 'scenario', $position, ['title' => $title, 'context' => $context, 'prompt' => '¿Qué protege mejor a la comunidad escolar?', 'accessible_text' => $context.' Compará visibilidad, accesibilidad y coordinación.', 'choices' => [['id' => 'improvisa', 'label' => $unsafe, 'feedback' => 'La improvisación traslada riesgo a otras personas.', 'correct' => false], ['id' => 'protege', 'label' => $safe, 'feedback' => 'Correcto. Conserva una ruta protegida y comprensible.', 'correct' => true], ['id' => 'grupo', 'label' => 'Hacer lo que haga el grupo', 'feedback' => 'Un grupo también puede actuar con información incompleta.', 'correct' => false]]]);
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
