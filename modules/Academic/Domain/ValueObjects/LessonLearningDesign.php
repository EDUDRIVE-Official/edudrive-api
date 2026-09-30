<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final readonly class LessonLearningDesign
{
    // Activity format must never be used to infer the learner's life stage.
    private const array STAGES = ['pending_review', 'explore', 'discover', 'understand', 'prepare', 'drive', 'perfect', 'refresh', 'teach'];

    private const array EXPERIENCE_TYPES = ['story', 'visual_exploration', 'classification', 'ordering', 'dilemma', 'community_observation', 'guided_practice', 'web_simulation', 'simudrive_lab', 'competency_challenge', 'reflection'];

    /**
     * @param  list<string>  $jurisdictions
     * @param  list<string>  $indicatorCodes
     * @param  list<array{indicator_code: string, event_type: string, minimum_observations: int, weight: int}>  $evidenceRules
     * @param  list<array{url: string, reviewed_at: string}>  $normativeSources
     */
    private function __construct(
        public string $stage,
        public array $jurisdictions,
        public string $experienceType,
        public string $behaviorObjective,
        public string $competencyId,
        public string $subcompetencyCode,
        public array $indicatorCodes,
        public array $evidenceRules,
        public bool $requiresGuardian,
        public array $normativeSources,
        public int $version,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $stage = (string) ($data['stage'] ?? '');
        $type = (string) ($data['experience_type'] ?? '');
        $objective = trim((string) ($data['behavior_objective'] ?? ''));
        $competencyId = (string) ($data['competency_id'] ?? '');
        $subcompetencyCode = strtoupper(trim((string) ($data['subcompetency_code'] ?? '')));
        $jurisdictions = array_values(array_unique(array_map('strtoupper', (array) ($data['jurisdictions'] ?? []))));
        $indicatorCodes = array_values(array_unique(array_map(static fn (mixed $value): string => strtoupper(trim((string) $value)), (array) ($data['indicator_codes'] ?? []))));
        $rawRules = array_values((array) ($data['evidence_rules'] ?? []));
        $rawSources = array_values((array) ($data['normative_sources'] ?? []));
        $version = (int) ($data['version'] ?? 1);

        if (! in_array($stage, self::STAGES, true) || ! in_array($type, self::EXPERIENCE_TYPES, true)) {
            throw new InvalidArgumentException('La etapa o el tipo de experiencia no es válido.');
        }
        if ($objective === '' || ! preg_match('/^[0-9a-f-]{36}$/i', $competencyId) || $subcompetencyCode === '' || $jurisdictions === [] || $indicatorCodes === [] || $rawRules === [] || $version < 1) {
            throw new InvalidArgumentException('El diseño pedagógico de la lección está incompleto.');
        }

        $rules = [];
        foreach ($rawRules as $rule) {
            if (! is_array($rule) || trim((string) ($rule['indicator_code'] ?? '')) === '' || trim((string) ($rule['event_type'] ?? '')) === '' || (int) ($rule['minimum_observations'] ?? 0) < 1 || (int) ($rule['weight'] ?? 0) < 1) {
                throw new InvalidArgumentException('Una regla de evidencia no es válida.');
            }

            $rules[] = [
                'indicator_code' => strtoupper(trim((string) $rule['indicator_code'])),
                'event_type' => trim((string) $rule['event_type']),
                'minimum_observations' => (int) $rule['minimum_observations'],
                'weight' => (int) $rule['weight'],
            ];
        }

        $ruleIndicatorCodes = array_values(array_unique(array_column($rules, 'indicator_code')));
        sort($ruleIndicatorCodes);
        $declaredIndicatorCodes = $indicatorCodes;
        sort($declaredIndicatorCodes);
        if ($ruleIndicatorCodes !== $declaredIndicatorCodes) {
            throw new InvalidArgumentException('Cada indicador declarado debe tener una regla de evidencia correspondiente y no se permiten reglas ajenas a la lección.');
        }

        $sources = [];
        foreach ($rawSources as $source) {
            $url = is_array($source) ? trim((string) ($source['url'] ?? '')) : '';
            $reviewedAt = is_array($source) ? trim((string) ($source['reviewed_at'] ?? '')) : '';
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $reviewedAt);
            if (! is_array($source) || filter_var($url, FILTER_VALIDATE_URL) === false || ! str_starts_with($url, 'https://') || $date === false || $date->format('Y-m-d') !== $reviewedAt) {
                throw new InvalidArgumentException('Una fuente normativa no es válida.');
            }
            $sources[] = ['url' => $url, 'reviewed_at' => $reviewedAt];
        }

        return new self($stage, $jurisdictions, $type, $objective, $competencyId, $subcompetencyCode, $indicatorCodes, $rules, (bool) ($data['requires_guardian'] ?? false), $sources, $version);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'stage' => $this->stage, 'jurisdictions' => $this->jurisdictions,
            'experience_type' => $this->experienceType, 'behavior_objective' => $this->behaviorObjective,
            'competency_id' => $this->competencyId, 'subcompetency_code' => $this->subcompetencyCode,
            'indicator_codes' => $this->indicatorCodes, 'evidence_rules' => $this->evidenceRules,
            'requires_guardian' => $this->requiresGuardian, 'normative_sources' => $this->normativeSources,
            'version' => $this->version,
        ];
    }

    public function sourcesAreCurrent(DateTimeInterface $asOf, int $maximumAgeDays = 365): bool
    {
        if ($this->normativeSources === [] || $maximumAgeDays < 1) {
            return false;
        }

        foreach ($this->normativeSources as $source) {
            $reviewedAt = new DateTimeImmutable($source['reviewed_at'].' 00:00:00');
            $ageInDays = ($asOf->getTimestamp() - $reviewedAt->getTimestamp()) / 86400;
            if ($ageInDays < 0 || $ageInDays > $maximumAgeDays) {
                return false;
            }
        }

        return true;
    }
}
