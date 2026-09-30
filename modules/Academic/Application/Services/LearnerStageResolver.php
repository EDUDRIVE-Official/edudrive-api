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
        if ($dateOfBirth === null || $dateOfBirth > $today || $dateOfBirth->diff($today)->y < 3) {
            return [
                'stage' => 'universal',
                'identity' => 'Ciudadano Vial',
                'age_range' => 'Etapa por confirmar',
                'guidance' => 'Confirmá tu fecha de nacimiento en Mi perfil. Si tenés menos de 3 años, pedí orientación a una persona adulta antes de elegir actividades.',
                'requires_guardian' => true,
                'instruction' => 'Leé el caso, explorá la animación y justificá la decisión que reduce mejor el riesgo.',
                'reflection_prompt' => '¿Qué cambiarías en un recorrido cotidiano después de esta lección?',
                'practice_mode' => 'Ensayo en un espacio protegido, con orientación hasta confirmar la etapa.',
            ];
        }

        $age = $dateOfBirth->diff($today)->y;

        return match (true) {
            $age <= 6 => $this->stage('E1', 'DESCUBRO', '3–6 años', $age <= 4 ? '3–4: reconocer, imitar y comunicar con gestos o imágenes.' : '5–6: explicar una elección sencilla y ensayar una variante.', true, 'Observá con una persona adulta y señalá un lugar seguro.', 'Mostrá o contá qué harías con tu acompañante.', 'Juego en un espacio protegido; acompañamiento adulto permanente.'),
            $age <= 12 => $this->stage('E2', 'COMPRENDO', '7–12 años', $age <= 9 ? '7–9: comparar peligros concretos y ensayar con apoyo.' : '10–12: planificar rutas y alternativas ante un imprevisto.', true, 'Compará las opciones y explicá cuál reduce el peligro.', '¿Qué alternativa elegirías si cambia el entorno?', 'Práctica protegida con apoyo. La autonomía se acuerda con cuidadores según el entorno y el desempeño.'),
            $age <= 16 => $this->stage('E3', 'DECIDO', '13–16 años', $age <= 14 ? '13–14: anticipar consecuencias y practicar cómo expresar una decisión segura.' : '15–16: integrar presión social, evidencia y responsabilidad futura.', true, 'Anticipá consecuencias y decidí cómo responder a la presión.', '¿Qué te haría cambiar de decisión?', 'Análisis de variantes desde un lugar protegido.'),
            default => $this->stage('E4', 'CONDUZCO', '17+ años', 'Elegí movilidad cotidiana, automóvil o motocicleta en Mi perfil. Ingresás por diagnóstico, sin completar cursos infantiles. La ruta no habilita legalmente para conducir.', $age < 18, 'Identificá lo que necesitás aprender según tu rol y experiencia.', '¿Qué evidencia te falta para aplicar esta decisión en tu contexto?', 'Diagnóstico y práctica según el rol; conducción sujeta a requisitos legales y supervisión.'),
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
