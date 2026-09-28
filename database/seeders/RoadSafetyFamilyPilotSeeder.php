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

final class RoadSafetyFamilyPilotSeeder extends Seeder
{
    private const string COURSE_CODE = 'EDU-EXP-009';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || DB::table('academic_courses')->where('code', self::COURSE_CODE)->exists()) {
            return;
        }
        $competencyId = $this->competency();
        $course = app(CreateCourseHandler::class)->handle(new CreateCourseCommand(code: self::COURSE_CODE, title: 'Misión Familia Vial', description: 'Herramientas para acompañar el aprendizaje vial con ejemplo, preguntas y prácticas protegidas.', objectives: 'Modelar conductas coherentes; preparar prácticas por edad; observar sin exponer; conversar y registrar evidencia útil.', prerequisites: 'Dirigido a personas adultas referentes. No autoriza prácticas en tránsito ni sustituye supervisión profesional.', modality: 'virtual', durationHours: 1));
        $module = $this->id('MOD');
        $units = [$this->id('MODELA'), $this->id('ACOMPANA'), $this->id('OBSERVA')];
        app(ReplaceCourseCurriculumHandler::class)->handle(new ReplaceCourseCurriculumCommand($course->id, [new CourseModuleInput($module, 'MISION-FAMILIA-VIAL', 'Misión: Enseñamos con el ejemplo', 'Tres retos para convertir recorridos cotidianos en aprendizaje seguro.', 'Modelar, acompañar y registrar progreso sin miedo ni exposición.', 54, 1, [], [
            new CourseUnitInput($units[0], 'FAMILIA-MODELA', '1. Lo que hacemos también enseña', 'Alineá palabras y conducta adulta.', 'Mostrar decisiones observables y explicables.', 18, 1, []),
            new CourseUnitInput($units[1], 'FAMILIA-ACOMPANA', '2. Prácticas apropiadas para la edad', 'Prepará lugar, límites y apoyo.', 'Acompañar sin sustituir el criterio ni exponer.', 18, 2, [$units[0]]),
            new CourseUnitInput($units[2], 'FAMILIA-OBSERVA', '3. Observar para ayudar a crecer', 'Usá preguntas y evidencia concreta.', 'Registrar conducta, contexto y reflexión sin etiquetar.', 18, 3, [$units[1]]),
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
        $id = DB::table('academic_competencies')->where('code', 'FAMILIA-VIAL')->value('id');
        if ($id !== null) {
            return (string) $id;
        }
        $c = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand('FAMILIA-VIAL', 'Acompaña el desarrollo de ciudadanía vial', 'Modela, prepara prácticas y registra observaciones confiables.', 'vulnerable_road_users', 'foundation'));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($c->id, 'FAMILIA.ACOMPANA', 'Modela y acompaña aprendizaje vial por etapas'));
        foreach ([['FAMILIA.MODELA', 'Explica y demuestra decisiones coherentes.'], ['FAMILIA.PRACTICA', 'Prepara experiencias protegidas acordes con la edad.'], ['FAMILIA.EVIDENCIA', 'Describe observaciones y reflexiones sin etiquetar.']] as [$code,$description]) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($c->id, 'FAMILIA.ACOMPANA', $code, $description));
        }

        return $c->id;
    }

    private function specs(): array
    {
        return [
            ['FAM-MODELA', 'El ejemplo ocurre todos los días', 'FAMILIA.MODELA', 'story', 'Explicar en voz alta una decisión segura y coherente.', 'Las niñas y niños comparan lo que escuchan con lo que ven. Decir “esperá la señal” y luego cruzar con prisa debilita el aprendizaje. Modelar es detenerse, observar y explicar brevemente qué información guía la decisión.', ['Llegan tarde', 'La persona adulta considera cruzar fuera del paso porque hay prisa.', 'Nombrar la prisa y mantener la ruta segura', 'Decir que no lo imite y cruzar'], ['Adulto distraído', 'Llega un mensaje antes del cruce familiar.', 'Alejarse del borde, resolverlo y reconstruir el plan', 'Leerlo mientras la niña observa el tránsito'], 'Elegí una conducta y ensayá una explicación corta: qué veo, qué espero y por qué cambio o continúo.'],
            ['FAM-PRACTICA', 'Acompañar sin empujar el resultado', 'FAMILIA.PRACTICA', 'guided_practice', 'Preparar una práctica con límites y dificultad apropiados.', 'Una práctica comienza en casa o en un espacio sin tránsito. La persona adulta define objetivo, zona protegida, señal para detenerse y ayuda disponible. Acompañar no es resolver todo: es hacer preguntas sin soltar la seguridad.', ['Primera observación', 'Una niña pequeña quiere practicar en una intersección compleja.', 'Usar maqueta o cruce simple protegido con apoyo cercano', 'Aceptar para que aprenda del entorno real'], ['Respuesta incorrecta', 'La niña elige una opción insegura durante una simulación.', 'Preguntar qué pista faltó y permitir otro intento', 'Regañar y terminar la actividad'], 'Diseñá una práctica con objetivo, lugar, duración, criterio de pausa y una pregunta de reflexión.'],
            ['FAM-EVIDENCIA', 'Registrar conducta, no etiquetas', 'FAMILIA.EVIDENCIA', 'competency_challenge', 'Describir observación, contexto, apoyo y siguiente paso.', '“Es irresponsable” no explica qué ocurrió. Una evidencia útil indica conducta observable, contexto, ayuda recibida y reflexión. El Pasaporte Vial muestra evolución; no compara hermanos ni convierte un intento en identidad.', ['Cruce acompañado', 'La persona menor se detuvo y observó, pero necesitó recordar mirar un segundo carril.', 'Registrar logro, apoyo y próximo paso concreto', 'Marcar simplemente que no sabe cruzar'], ['Dato privado', 'La reflexión incluye dirección, horario y nombres de terceros.', 'Registrar solo el contexto necesario sin datos identificables', 'Guardar todo para que la evidencia parezca completa'], 'Escribí una observación ficticia con fecha general, conducta, apoyo, reflexión y siguiente práctica; nunca incluyás ubicación precisa.'],
        ];
    }

    private function lesson(array $s, string $competencyId): LessonInput
    {
        [$code,$title,$indicator,$experience,$objective,$text,$a,$b,$practice] = $s;
        $stage = 'pending_review';

        return new LessonInput($this->id($code), $code, $title, $objective, 18, 1, [$this->text(1, 'Idea esencial', $text), $this->scenario(2, $a), $this->scenario(3, $b), $this->text(4, 'Práctica familiar', $practice)], LessonLearningDesign::fromArray(['stage' => $stage, 'jurisdictions' => ['GLOBAL', 'CR'], 'experience_type' => $experience, 'behavior_objective' => $objective, 'competency_id' => $competencyId, 'subcompetency_code' => 'FAMILIA.ACOMPANA', 'indicator_codes' => [$indicator], 'evidence_rules' => [['indicator_code' => $indicator, 'event_type' => 'lesson_completed', 'minimum_observations' => 1, 'weight' => 1]], 'requires_guardian' => false, 'normative_sources' => [['url' => 'https://mep.go.cr/programas-proyectos/camino-seguro', 'reviewed_at' => '2026-09-13']], 'version' => 1]));
    }

    private function scenario(int $p, array $d): ContentBlockInput
    {
        [$title,$context,$safe,$unsafe] = $d;

        return new ContentBlockInput((string) Str::uuid(), 'scenario', $p, ['title' => $title, 'context' => $context, 'prompt' => '¿Qué acompañamiento desarrolla criterio y protección?', 'accessible_text' => $context.' Compará ejemplo, seguridad, autonomía y privacidad.', 'choices' => [['id' => 'impulso', 'label' => $unsafe, 'feedback' => 'Esta opción contradice el ejemplo, aumenta exposición o convierte el error en juicio.', 'correct' => false], ['id' => 'acompanar', 'label' => $safe, 'feedback' => 'Correcto. Mantiene protección y transforma la experiencia en aprendizaje.', 'correct' => true], ['id' => 'resolver', 'label' => 'Tomar toda la decisión sin explicarla', 'feedback' => 'Resolver protege el momento, pero no ayuda a construir criterio para el futuro.', 'correct' => false]]]);
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
