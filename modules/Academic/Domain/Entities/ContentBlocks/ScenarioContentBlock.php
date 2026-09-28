<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\ContentBlocks;

use Modules\Academic\Domain\Enums\ContentBlockType;
use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;

final readonly class ScenarioContentBlock implements ContentBlock
{
    /** @param list<array{id: string, label: string, feedback: string, correct: bool}> $choices */
    private function __construct(
        private ContentBlockId $id,
        private int $position,
        private string $title,
        private string $context,
        private string $prompt,
        private string $accessibleText,
        private array $choices,
        private ?bool $stopAtDecisionPoint,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromPayload(ContentBlockId $id, int $position, array $payload): self
    {
        if ($position < 1 || array_diff(array_keys($payload), ['title', 'context', 'prompt', 'accessible_text', 'choices', 'stop_at_decision_point']) !== []) {
            throw InvalidContentBlock::create();
        }

        if (array_key_exists('stop_at_decision_point', $payload) && ! is_bool($payload['stop_at_decision_point'])) {
            throw InvalidContentBlock::create();
        }

        $choices = [];
        foreach ((array) ($payload['choices'] ?? []) as $choice) {
            if (! is_array($choice) || array_diff(array_keys($choice), ['id', 'label', 'feedback', 'correct']) !== []) {
                throw InvalidContentBlock::create();
            }
            $choices[] = [
                'id' => self::text($choice, 'id'),
                'label' => self::text($choice, 'label'),
                'feedback' => self::text($choice, 'feedback'),
                'correct' => filter_var($choice['correct'] ?? null, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? throw InvalidContentBlock::create(),
            ];
        }

        $choiceIds = array_column($choices, 'id');
        if (
            count($choices) < 2
            || count(array_unique($choiceIds)) !== count($choiceIds)
            || count(array_filter($choices, static fn (array $choice): bool => $choice['correct'])) !== 1
        ) {
            throw InvalidContentBlock::create();
        }

        return new self(
            $id,
            $position,
            self::text($payload, 'title'),
            self::text($payload, 'context'),
            self::text($payload, 'prompt'),
            self::text($payload, 'accessible_text'),
            $choices,
            $payload['stop_at_decision_point'] ?? null,
        );
    }

    public function id(): ContentBlockId
    {
        return $this->id;
    }

    public function type(): ContentBlockType
    {
        return ContentBlockType::Scenario;
    }

    public function position(): int
    {
        return $this->position;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $payload = ['title' => $this->title, 'context' => $this->context, 'prompt' => $this->prompt, 'accessible_text' => $this->accessibleText, 'choices' => $this->choices];
        if ($this->stopAtDecisionPoint !== null) {
            $payload['stop_at_decision_point'] = $this->stopAtDecisionPoint;
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private static function text(array $payload, string $key): string
    {
        $value = trim((string) ($payload[$key] ?? ''));
        if ($value === '' || mb_strlen($value) > 5000) {
            throw InvalidContentBlock::create();
        }

        return $value;
    }
}
