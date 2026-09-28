<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\{AddCompetencyIndicatorCommand, AddSubcompetencyCommand, ApproveCourseCommand, CreateCompetencyCommand, CreateCourseCommand, PublishCourseCommand, ReplaceCourseCurriculumCommand, ReplaceUnitContentCommand, SubmitCourseForReviewCommand};
use Modules\Academic\Application\DTO\{ContentBlockInput, CourseModuleInput, CourseUnitInput, LessonInput};
use Modules\Academic\Application\UseCases\{AddCompetencyIndicatorHandler, AddSubcompetencyHandler, ApproveCourseHandler, CreateCompetencyHandler, CreateCourseHandler, PublishCourseHandler, ReplaceCourseCurriculumHandler, ReplaceUnitContentHandler, SubmitCourseForReviewHandler};
use Modules\Academic\Domain\ValueObjects\LessonLearningDesign;
use Ramsey\Uuid\Uuid;

class NextRoadEducationCoursesSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) return;

        foreach ($this->courses() as $course) {
            if (! DB::table('academic_courses')->where('code', $course['code'])->exists()) {
                $this->createCourse($course);
            }
        }
    }

    private function createCourse(array $spec): void
    {
        $competencyId = $this->competency($spec);
        $course = app(CreateCourseHandler::class)->handle(new CreateCourseCommand(
            code: $spec['code'], title: $spec['title'], description: $spec['description'],
            objectives: $spec['objectives'], prerequisites: $spec['prerequisites'], modality: 'virtual', durationHours: 1,
        ));
        $moduleId = $this->id($spec['code'], 'MOD');
        $unitIds = array_map(fn (array $unit) => $this->id($spec['code'], $unit['code']), $spec['units']);
        $units = [];
        foreach ($spec['units'] as $index => $unit) {
            $units[] = new CourseUnitInput($unitIds[$index], $unit['code'], ($index + 1).'. '.$unit['title'], $unit['summary'], $unit['objective'], 18, $index + 1, $index ? [$unitIds[$index - 1]] : []);
        }
        app(ReplaceCourseCurriculumHandler::class)->handle(new ReplaceCourseCurriculumCommand($course->id, [
            new CourseModuleInput($moduleId, $spec['module_code'], $spec['module_title'], $spec['module_summary'], $spec['objectives'], 54, 1, [], $units),
        ]));
        foreach ($spec['units'] as $index => $unit) {
            app(ReplaceUnitContentHandler::class)->handle(new ReplaceUnitContentCommand($course->id, $unitIds[$index], [$this->lesson($spec, $unit, $competencyId, $index)]));
        }
        app(SubmitCourseForReviewHandler::class)->handle(new SubmitCourseForReviewCommand($course->id));
        app(ApproveCourseHandler::class)->handle(new ApproveCourseCommand($course->id));
        app(PublishCourseHandler::class)->handle(new PublishCourseCommand($course->id));
    }

    private function competency(array $spec): string
    {
        $existing = DB::table('academic_competencies')->where('code', $spec['competency'])->value('id');
        if ($existing !== null) return (string) $existing;
        $competency = app(CreateCompetencyHandler::class)->handle(new CreateCompetencyCommand($spec['competency'], $spec['competency_title'], $spec['competency_description'], 'risk_management', 'foundation'));
        app(AddSubcompetencyHandler::class)->handle(new AddSubcompetencyCommand($competency->id, $spec['subcompetency'], $spec['competency_description']));
        foreach ($spec['units'] as $unit) {
            app(AddCompetencyIndicatorHandler::class)->handle(new AddCompetencyIndicatorCommand($competency->id, $spec['subcompetency'], str_replace('_', '', $unit['indicator']), $unit['objective']));
        }
        return $competency->id;
    }

    private function lesson(array $course, array $unit, string $competencyId, int $index): LessonInput
    {
        $experiences = ['visual_exploration', 'guided_practice', 'competency_challenge'];

        $indicator = str_replace('_', '', $unit['indicator']);
        return new LessonInput($this->id($course['code'], 'LESSON-'.$unit['code']), 'LECCION-'.$unit['code'], $unit['lesson_title'], $unit['objective'], 18, 1, [
            $this->text(1, 'Idea esencial', $unit['content']),
            $this->scenario(2, $unit['scenarios'][0], $course['prompt']),
            $this->scenario(3, $unit['scenarios'][1], $course['prompt']),
            $this->text(4, 'Práctica y evidencia', $unit['practice']),
        ], LessonLearningDesign::fromArray([
            'stage' => 'pending_review', 'jurisdictions' => ['GLOBAL', 'CR'], 'experience_type' => $experiences[$index],
            'behavior_objective' => $unit['objective'], 'competency_id' => $competencyId, 'subcompetency_code' => $course['subcompetency'],
            'indicator_codes' => [$indicator], 'evidence_rules' => [['indicator_code' => $indicator, 'event_type' => 'lesson_completed', 'minimum_observations' => 1, 'weight' => 1]],
            'requires_guardian' => true, 'normative_sources' => [['url' => 'https://www.csv.go.cr/seguridad-vial-virtual1', 'reviewed_at' => '2026-09-13']], 'version' => 1,
        ]));
    }

    private function scenario(int $position, array $data, string $prompt): ContentBlockInput
    {
        [$title, $context, $safe, $unsafe, $feedback] = $data;
        return new ContentBlockInput((string) Str::uuid(), 'scenario', $position, [
            'title' => $title, 'context' => $context, 'prompt' => $prompt, 'accessible_text' => $context.' Compará protección, autonomía, previsibilidad y margen.',
            'choices' => [
                ['id' => 'riesgo', 'label' => $unsafe, 'feedback' => 'Esta opción reduce el margen o ignora necesidades de otras personas.', 'correct' => false],
                ['id' => 'segura', 'label' => $safe, 'feedback' => $feedback, 'correct' => true],
                ['id' => 'esperar', 'label' => 'Continuar igual y esperar que otra persona resuelva', 'feedback' => 'La seguridad compartida requiere observar, comunicar y adaptar la acción.', 'correct' => false],
            ],
        ]);
    }

    private function text(int $position, string $title, string $markdown): ContentBlockInput
    {
        return new ContentBlockInput((string) Str::uuid(), 'text', $position, compact('title', 'markdown'));
    }

    private function id(string $course, string $suffix): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_DNS, 'edudrive.'.$course.'.'.$suffix)->toString();
    }

    protected function courses(): array
    {
        return [
            [
                'code' => 'EDU-EXP-010', 'title' => 'Misión Movilidad Inclusiva', 'module_code' => 'MISION-MOVILIDAD-INCLUSIVA', 'module_title' => 'Misión: Una vía para todas las personas',
                'description' => 'Aprende a reconocer barreras y compartir la movilidad con personas de distintas edades y capacidades.',
                'module_summary' => 'Tres retos para observar barreras, ofrecer apoyo respetuoso y diseñar recorridos accesibles.',
                'objectives' => 'Reconocer barreras físicas, sensoriales y cognitivas; ofrecer ayuda con consentimiento; adaptar decisiones para proteger autonomía y dignidad.',
                'prerequisites' => 'Para todas las edades. Las prácticas de menores se realizan con acompañamiento y fuera del tránsito activo.',
                'competency' => 'MOVILIDAD-INCLUSIVA', 'competency_title' => 'Convive en una movilidad accesible', 'competency_description' => 'Reconoce barreras y adapta su conducta respetando la autonomía.', 'subcompetency' => 'INCLUSION.CONVIVE',
                'prompt' => '¿Qué decisión protege accesibilidad, autonomía y dignidad?',
                'units' => [
                    ['code'=>'INCLUSION-BARRERAS','title'=>'Ver las barreras','summary'=>'Observá el recorrido desde distintas necesidades.','objective'=>'Identificar obstáculos físicos, sensoriales y cognitivos.','indicator'=>'INCLUSION.OBSERVA','lesson_title'=>'La barrera está en el entorno','content'=>'Una acera bloqueada, una señal sin contraste, ruido intenso o poco tiempo para cruzar pueden excluir. La capacidad de una persona no explica por sí sola el riesgo: el diseño y la conducta de quienes comparten la vía también lo crean o lo reducen.','scenarios'=>[['Acera interrumpida','Un vehículo bloquea la rampa y una persona usa silla de ruedas.','Mantener libre la rampa y buscar un paso accesible','Decirle que baje a la calzada','Correcto. El recorrido accesible debe permanecer continuo.'],['Señal difícil de percibir','Una persona no identifica con claridad cuándo inicia el cruce.','Describir la situación y acompañar solo si acepta','Jalarla del brazo sin avisar','Correcto. La información y el consentimiento protegen autonomía.']],'practice'=>'Recorré un espacio seguro y registrá una barrera, a quién podría afectar y una mejora posible sin fotografiar personas.'],
                    ['code'=>'INCLUSION-APOYO','title'=>'Preguntar antes de ayudar','summary'=>'Ofrecé apoyo sin imponerlo.','objective'=>'Comunicar y ofrecer ayuda respetando consentimiento.','indicator'=>'INCLUSION.APOYA','lesson_title'=>'Ayuda que no quita autonomía','content'=>'Preguntar “¿Desea ayuda?” permite que la persona indique qué necesita. No se toca una silla de ruedas, bastón, animal de asistencia ni el cuerpo de otra persona sin permiso. Dar tiempo también es una forma de cuidado.','scenarios'=>[['Cruce con más tiempo','Una persona mayor avanza lentamente y la señal va a cambiar.','Reducir presión, proteger el espacio y seguir su indicación','Apurarla desde atrás con la bocina','Correcto. El tiempo adicional evita presión y caídas.'],['Animal de asistencia','Un perro de asistencia espera con su persona.','No distraerlo y hablar directamente con la persona','Acariciarlo para saludar','Correcto. El animal está trabajando y no debe distraerse.']],'practice'=>'Ensayá tres frases: ofrecer ayuda, preguntar cómo ayudar y aceptar con respeto cuando la respuesta sea no.'],
                    ['code'=>'INCLUSION-DISENA','title'=>'Planear para todas las personas','summary'=>'Convertí observaciones en mejoras.','objective'=>'Proponer rutas y acuerdos con accesibilidad universal.','indicator'=>'INCLUSION.DISENA','lesson_title'=>'Accesibilidad desde el inicio','content'=>'El diseño universal busca que un recorrido pueda ser comprendido y usado por la mayor diversidad posible. Rutas continuas, contraste, información visual y sonora, descanso y tiempo suficiente benefician a toda la comunidad.','scenarios'=>[['Actividad escolar','La ruta propuesta tiene gradas y una única explicación escrita.','Añadir ruta sin gradas e instrucciones visuales, sonoras y simples','Pedir adaptaciones solo si alguien se queja','Correcto. Planear diversidad desde el inicio evita exclusión.'],['Punto de encuentro','El grupo define un sitio estrecho junto al tránsito.','Elegir un espacio accesible, visible y alejado del flujo','Mantenerlo porque queda más cerca','Correcto. Acceso y zona de espera deben funcionar juntos.']],'practice'=>'Diseñá un recorrido inclusivo con entrada, cruce, descanso, información y alternativa; añadilo como evidencia al Pasaporte Vial.'],
                ],
            ],
            [
                'code' => 'EDU-EXP-011', 'title' => 'Misión Visibilidad, Lluvia y Noche', 'module_code' => 'MISION-CLIMA-NOCHE', 'module_title' => 'Misión: Ver, ser visto y conservar margen',
                'description' => 'Decisiones seguras cuando disminuyen la visibilidad y la adherencia.', 'module_summary' => 'Tres retos para preparar, adaptar y decidir cuándo detener el recorrido.',
                'objectives' => 'Comprobar visibilidad; aumentar tiempo y espacio; reconocer condiciones en las que se debe cambiar o suspender el plan.',
                'prerequisites' => 'Aplica a peatones, ciclistas, pasajeros y conductores. No invita a menores a practicar en tránsito o clima adverso.',
                'competency' => 'VISIBILIDAD-CLIMA', 'competency_title' => 'Adapta el recorrido a visibilidad y clima', 'competency_description' => 'Prepara y modifica decisiones frente a lluvia, oscuridad y baja adherencia.', 'subcompetency' => 'CLIMA.ADAPTACION',
                'prompt' => '¿Qué decisión recupera visibilidad, adherencia y margen?',
                'units' => [
                    ['code'=>'CLIMA-PREPARA','title'=>'Preparar antes de salir','summary'=>'Revisá luz, ropa, ruta y pronóstico.','objective'=>'Comprobar condiciones y equipo antes del movimiento.','indicator'=>'CLIMA.PREPARA','lesson_title'=>'La seguridad comienza antes de la lluvia','content'=>'En Costa Rica una tarde puede cambiar rápido. Revisar el pronóstico, elegir una ruta conocida, usar elementos visibles y comprobar luces y limpiaparabrisas reduce sorpresas. Ser visible no significa asumir que ya te vieron.','scenarios'=>[['Atardecer cercano','Una familia inicia una caminata y oscurecerá antes del regreso.','Cambiar horario o llevar iluminación y ruta segura','Confiar en la luz del teléfono','Correcto. Preparar el regreso evita improvisación.'],['Bicicleta bajo lluvia','La luz trasera no funciona y empieza a llover.','Posponer o resolver la visibilidad antes de salir','Salir rápido antes de que llueva más','Correcto. Sin visibilidad suficiente no se inicia el trayecto.']],'practice'=>'Creá una lista previa para tu modo de transporte: clima, luz, ruta, equipo, comunicación y plan alterno.'],
                    ['code'=>'CLIMA-ADAPTA','title'=>'Más distancia, menos velocidad','summary'=>'Adaptá el movimiento a lo que realmente podés ver.','objective'=>'Aumentar margen ante menor visibilidad y adherencia.','indicator'=>'CLIMA.ADAPTA','lesson_title'=>'Moverse según el alcance de la vista','content'=>'Lluvia, neblina, reflejos y oscuridad reducen información. La velocidad debe permitir detenerse dentro del espacio visible. Peatones y ciclistas necesitan confirmar que fueron percibidos; quien conduce evita maniobras bruscas y aumenta distancia.','scenarios'=>[['Charco desconocido','Una bicicleta se acerca a un charco que oculta la superficie.','Reducir antes, mantener control y evitar movimientos bruscos','Atravesarlo rápido para no mojarse','Correcto. Lo que no se ve exige más margen.'],['Reflejo nocturno','El pavimento mojado dificulta ver marcas y personas.','Reducir velocidad y ampliar distancia de seguimiento','Seguir igual usando luces altas permanentemente','Correcto. La adaptación depende de la visibilidad real.']],'practice'=>'En una simulación, compará cuánto cambia el tiempo disponible al duplicar distancia y reducir velocidad. Explicá tu decisión.'],
                    ['code'=>'CLIMA-DETENTE','title'=>'Saber cambiar el plan','summary'=>'Reconocé cuándo continuar deja de ser razonable.','objective'=>'Suspender o reubicar el recorrido cuando el margen desaparece.','indicator'=>'CLIMA.DETIENE','lesson_title'=>'Detenerse también es una buena decisión','content'=>'Si no se distingue la ruta, hay inundación, caída de objetos o fatiga visual, continuar puede no ser seguro. Se busca un lugar protegido fuera del flujo, se comunica el cambio y nunca se atraviesa agua cuyo nivel o corriente no se conoce.','scenarios'=>[['Calle inundada','Otros vehículos cruzan, pero no se conoce profundidad ni corriente.','No cruzar y buscar una ruta segura autorizada','Seguir las huellas del vehículo anterior','Correcto. El comportamiento ajeno no demuestra que el paso sea seguro.'],['Lluvia extrema','La visibilidad cae casi por completo durante el viaje.','Salir del flujo hacia un lugar permitido y protegido','Detenerse en medio del carril','Correcto. La pausa debe hacerse sin crear un nuevo obstáculo.']],'practice'=>'Construí un semáforo personal: verde para continuar, amarillo para adaptar y rojo para detenerte o cambiar la ruta.'],
                ],
            ],
            [
                'code' => 'EDU-EXP-012', 'title' => 'Misión Zonas de Obra y Rutas Cambiantes', 'module_code' => 'MISION-OBRAS-RUTA', 'module_title' => 'Misión: Leer una vía que cambió',
                'description' => 'Aprende a desplazarte con seguridad cuando obras, desvíos o servicios de emergencia modifican la ruta.', 'module_summary' => 'Tres retos para detectar el cambio, seguir control temporal y reconstruir el recorrido.',
                'objectives' => 'Reconocer señalización temporal; proteger a personas trabajadoras y usuarias vulnerables; decidir una alternativa segura sin improvisar.',
                'prerequisites' => 'Para todas las edades y modos de transporte. Las observaciones se realizan desde espacios protegidos.',
                'competency' => 'RUTA-CAMBIANTE', 'competency_title' => 'Se adapta a cambios temporales de la vía', 'competency_description' => 'Interpreta controles temporales y reconstruye su ruta con previsibilidad.', 'subcompetency' => 'RUTA.CAMBIA',
                'prompt' => '¿Qué decisión respeta el control temporal y mantiene una ruta predecible?',
                'units' => [
                    ['code'=>'OBRA-DETECTA','title'=>'Detectar que la vía cambió','summary'=>'Buscá señales, conos, personal y trayectorias nuevas.','objective'=>'Reconocer información temporal antes de llegar al conflicto.','indicator'=>'RUTA.DETECTA','lesson_title'=>'La ruta habitual puede dejar de serlo','content'=>'Conos, barreras, maquinaria, señales provisionales y personal autorizado anuncian que el espacio funciona distinto. La costumbre no tiene prioridad sobre la condición presente. Observar temprano evita maniobras repentinas.','scenarios'=>[['Acera cerrada','Una barrera desvía a peatones antes de una obra.','Seguir el desvío protegido y confirmar el próximo cruce','Pasar entre las barreras porque la acera parece libre','Correcto. Una barrera puede proteger de riesgos no visibles.'],['Carril reducido','Conos anuncian el cierre más adelante.','Integrarse con anticipación y comunicación','Avanzar hasta el último cono y forzar el ingreso','Correcto. La previsibilidad reduce conflictos.']],'practice'=>'Dibujá una vía normal y su versión con obra; marcá qué señales cambian tus decisiones antes del peligro.'],
                    ['code'=>'OBRA-COOPERA','title'=>'Cooperar con el control temporal','summary'=>'Respetá indicaciones y a quienes trabajan.','objective'=>'Actuar de forma predecible frente a controles temporales.','indicator'=>'RUTA.COOPERA','lesson_title'=>'Una indicación coordina a muchas personas','content'=>'El personal autorizado y la señalización temporal coordinan movimientos incompatibles. Se reduce la velocidad, se evita distraer a quienes trabajan y se conserva distancia respecto de maquinaria, zanjas y vehículos detenidos.','scenarios'=>[['Paleta de alto','La señal habitual permite avanzar, pero una persona autorizada indica detenerse.','Detenerse y esperar la nueva indicación','Seguir la señal habitual','Correcto. El control temporal responde a una condición activa.'],['Maquinaria maniobrando','Un equipo pesado retrocede cerca del paso habilitado.','Mantener distancia y esperar confirmación clara','Pasar detrás rápidamente','Correcto. Los puntos ciegos de maquinaria requieren separación.']],'practice'=>'Representá con otra persona una zona de paso alternado: señal clara, confirmación, espera y avance sin competir.'],
                    ['code'=>'OBRA-REPLANEA','title'=>'Reconstruir la ruta','summary'=>'Elegí una alternativa accesible y comunicada.','objective'=>'Replanificar sin invadir zonas restringidas ni trasladar el riesgo.','indicator'=>'RUTA.REPLANEA','lesson_title'=>'Un desvío también necesita evaluación','content'=>'La alternativa más corta no siempre es la más segura. Se comparan iluminación, cruces, accesibilidad, superficie y tráfico. Si el desvío no sirve para todas las personas del grupo, se busca apoyo o una ruta distinta y se comunica el cambio.','scenarios'=>[['Desvío sin acera','La alternativa indicada obliga a caminar junto al flujo y hay otra ruta protegida más larga.','Usar la ruta protegida y comunicar el retraso','Tomar el borde de la calzada para ahorrar tiempo','Correcto. El tiempo no compensa perder protección.'],['Ruta desconocida','Una aplicación propone atravesar una zona restringida.','Respetar el cierre y confirmar una alternativa permitida','Seguirla porque el mapa lo recomienda','Correcto. La condición física presente prevalece sobre el mapa.']],'practice'=>'Compará dos desvíos con cinco criterios: protección, accesibilidad, visibilidad, complejidad y plan B. Guardá la justificación en tu Pasaporte Vial.'],
                ],
            ],
        ];
    }
}
