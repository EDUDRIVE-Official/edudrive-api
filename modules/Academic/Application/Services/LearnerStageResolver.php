<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Services;

use DateTimeImmutable;
use DateTimeInterface;

final class LearnerStageResolver
{
    /**
     * @return array{stage: string, identity: string, age_range: string, guidance: string, requires_guardian: bool, instruction: string, reflection_prompt: string, practice_mode: string}
     */
    public function resolve(?DateTimeInterface $dateOfBirth, ?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today');
        if ($dateOfBirth === null || $dateOfBirth > $today || $dateOfBirth->diff($today)->y < 5) {
            return [
                'stage' => 'universal',
                'identity' => 'Ciudadano Vial',
                'age_range' => 'Etapa por confirmar',
                'guidance' => 'Confirmá tu fecha de nacimiento en Mi perfil. Si tenés menos de 5 años, pedí orientación a una persona adulta antes de elegir actividades.',
                'requires_guardian' => true,
                'instruction' => 'Leé el caso, explorá la animación y justificá la decisión que reduce mejor el riesgo.',
                'reflection_prompt' => '¿Qué cambiarías en un recorrido cotidiano después de esta lección?',
                'practice_mode' => 'Ensayo en un espacio protegido, con orientación hasta confirmar la etapa.',
            ];
        }

        $age = $dateOfBirth->diff($today)->y;

        return match (true) {
            $age <= 8 => $this->stage('explore', 'Explorador Vial', '5–8 años', 'Observá las pistas, contá qué ves y practicá solamente junto a una persona adulta.', true, 'Mirá la escena con una persona adulta. Señalá personas, vehículos y lugares seguros antes de elegir.', 'Contale a tu acompañante qué pista te ayudó a decidir.', 'Juego de observación y ensayo en un espacio sin vehículos.'),
            $age <= 12 => $this->stage('discover', 'Aventurero Vial', '9–12 años', 'Elegí una opción, explicá por qué es segura y comprobala con una persona adulta.', true, 'Explorá todas las opciones y explicá qué podría ocurrir después de cada una.', '¿Qué peligro descubriste que antes no habías notado?', 'Misión acompañada de observación en una ruta conocida.'),
            $age <= 15 => $this->stage('understand', 'Aprendiz Vial', '13–15 años', 'Identificá el peligro oculto, compará consecuencias y defendé tu decisión.', true, 'Buscá el riesgo menos evidente, compará consecuencias y justificá tu decisión.', '¿Qué presión, distracción o suposición podría hacerte elegir mal?', 'Análisis acompañado de un punto real sin ingresar a la calzada.'),
            $age <= 17 => $this->stage('prepare', 'Aspirante Responsable', '16–17 años', 'Conectá la norma con el riesgo real y prepará un plan antes de actuar.', true, 'Diferenciá la regla, el peligro real y la acción preventiva que aplicarías.', '¿Por qué tener prioridad no elimina tu responsabilidad de comprobar?', 'Planificación acompañada de una ruta con alternativas seguras.'),
            $age <= 24 => $this->stage('drive', 'Conductor Responsable', '18–24 años', 'Anticipá errores propios y ajenos; conservá siempre una alternativa segura.', false, 'Analizá la escena como peatón y como conductor; anticipá el error posible de cada actor.', '¿Qué acción propia haría más predecible y segura la interacción?', 'Observación autónoma desde un punto protegido y revisión de hábitos.'),
            $age <= 59 => $this->stage('perfect', 'Ciudadano Vial Experimentado', '25–59 años', 'Revisá tus hábitos y pensá cómo modelar esta conducta para otras personas.', false, 'Contrastá la conducta recomendada con tus hábitos actuales y detectá automatismos.', '¿Qué ejemplo estás transmitiendo a niñas, niños u otras personas?', 'Aplicación cotidiana y, si corresponde, acompañamiento formativo a otra persona.'),
            default => $this->stage('refresh', 'Ciudadano Vial Activo', '60+ años', 'Evaluá las condiciones, tu comodidad y alternativas que mantengan una movilidad segura.', false, 'Tomate el tiempo necesario, evaluá visibilidad, comodidad y rutas alternativas.', '¿Qué condición personal o del entorno te indicaría que conviene esperar o cambiar de ruta?', 'Recorrido planificado, sin prisa y con una alternativa de movilidad disponible.'),
        };
    }

    /** @return array{stage: string, identity: string, age_range: string, guidance: string, requires_guardian: bool, instruction: string, reflection_prompt: string, practice_mode: string} */
    private function stage(string $stage, string $identity, string $ageRange, string $guidance, bool $requiresGuardian, string $instruction, string $reflectionPrompt, string $practiceMode): array
    {
        return [
            'stage' => $stage,
            'identity' => $identity,
            'age_range' => $ageRange,
            'guidance' => $guidance,
            'requires_guardian' => $requiresGuardian,
            'instruction' => $instruction,
            'reflection_prompt' => $reflectionPrompt,
            'practice_mode' => $practiceMode,
        ];
    }
}
