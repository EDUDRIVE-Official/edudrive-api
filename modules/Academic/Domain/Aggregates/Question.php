<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\MatchingResponse;
use Modules\Academic\Domain\Entities\Responses\MultiSelectResponse;
use Modules\Academic\Domain\Entities\Responses\OrderingResponse;
use Modules\Academic\Domain\Entities\Responses\QuestionResponse;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Entities\Responses\TrueFalseResponse;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;
use Modules\Academic\Domain\Exceptions\InvalidQuestionScore;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionMedia;

final class Question
{
    private const int MAX_PROMPT_LENGTH = 1000;
    private const int MAX_EXPLANATION_LENGTH = 2000;

    /** @param list<QuestionOption> $options
     *  @param  list<QuestionMedia>  $media */
    private function __construct(
        private QuestionId $id,
        private QuestionType $type,
        private CompetencyId $competencyId,
        private string $prompt,
        private int $score,
        private QuestionResponse $response,
        private array $options,
        private ?string $explanation,
        private array $media,
    ) {}

    /** @param list<QuestionOption> $options
     *  @param  list<QuestionMedia>  $media */
    public static function create(
        QuestionId $id,
        QuestionType $type,
        CompetencyId $competencyId,
        string $prompt,
        int $score,
        QuestionResponse $response,
        array $options,
        ?string $explanation = null,
        array $media = [],
    ): self {
        $question = new self($id, $type, $competencyId, $prompt, $score, $response, $options, $explanation, $media);
        $question->assertValid();

        return $question;
    }

    /** @param list<QuestionOption> $options
     *  @param  list<QuestionMedia>  $media */
    public static function restore(
        QuestionId $id,
        QuestionType $type,
        CompetencyId $competencyId,
        string $prompt,
        int $score,
        QuestionResponse $response,
        array $options,
        ?string $explanation = null,
        array $media = [],
    ): self {
        $question = new self($id, $type, $competencyId, $prompt, $score, $response, $options, $explanation, $media);
        $question->assertValid();

        return $question;
    }

    /** @param list<QuestionOption> $options
     *  @param  list<QuestionMedia>  $media */
    public function replace(
        QuestionType $type,
        string $prompt,
        int $score,
        QuestionResponse $response,
        array $options,
        ?string $explanation = null,
        array $media = [],
    ): void {
        $next = new self(
            $this->id,
            $type,
            $this->competencyId,
            $prompt,
            $score,
            $response,
            $options,
            $explanation,
            $media,
        );
        $next->assertValid();

        $this->type = $type;
        $this->prompt = $next->prompt;
        $this->score = $score;
        $this->response = $response;
        $this->options = $options;
        $this->explanation = $next->explanation;
        $this->media = $media;
    }

    public function id(): QuestionId
    {
        return $this->id;
    }

    public function type(): QuestionType
    {
        return $this->type;
    }

    public function competencyId(): CompetencyId
    {
        return $this->competencyId;
    }

    public function prompt(): string
    {
        return $this->prompt;
    }

    public function score(): int
    {
        return $this->score;
    }

    public function response(): QuestionResponse
    {
        return $this->response;
    }

    /** @return list<QuestionOption> */
    public function options(): array
    {
        return $this->options;
    }

    public function explanation(): ?string
    {
        return $this->explanation;
    }

    /** @return list<QuestionMedia> */
    public function media(): array
    {
        return $this->media;
    }

    private function assertValid(): void
    {
        $this->prompt = trim($this->prompt);
        if ($this->prompt === '' || strlen($this->prompt) > self::MAX_PROMPT_LENGTH) {
            throw InvalidQuestion::create();
        }

        $this->explanation = self::optionalString($this->explanation, self::MAX_EXPLANATION_LENGTH);

        if ($this->score < 1) {
            throw InvalidQuestionScore::create();
        }

        if ($this->type === QuestionType::Situational) {
            if ($this->media === []) {
                throw InvalidQuestion::create();
            }
        } elseif ($this->media !== []) {
            throw InvalidQuestion::create();
        }

        $this->assertResponseMatchesOptions();
    }

    private function assertResponseMatchesOptions(): void
    {
        $refIds = array_map(static fn (QuestionOption $option): string => $option->refId(), $this->options);

        if (count($refIds) !== count(array_unique($refIds))) {
            throw InvalidQuestion::create();
        }

        foreach ($this->options as $index => $option) {
            if ($option->position() !== $index + 1) {
                throw InvalidQuestion::create();
            }
        }

        if ($this->response instanceof TrueFalseResponse) {
            if ($this->options !== []) {
                throw InvalidQuestion::create();
            }

            return;
        }

        if ($this->response instanceof SingleChoiceResponse
            || $this->response instanceof MultiSelectResponse
            || $this->response instanceof OrderingResponse
        ) {
            if (count($this->options) < 2) {
                throw InvalidQuestion::create();
            }
            foreach ($this->options as $option) {
                if ($option->side() !== null) {
                    throw InvalidQuestion::create();
                }
            }

            if ($this->response instanceof SingleChoiceResponse) {
                self::assertRefExists($refIds, $this->response->optionId);

                return;
            }
            if ($this->response instanceof MultiSelectResponse) {
                foreach ($this->response->optionIds as $optionId) {
                    self::assertRefExists($refIds, $optionId);
                }

                return;
            }
            foreach ($this->response->itemIds as $itemId) {
                self::assertRefExists($refIds, $itemId);
            }

            return;
        }

        if ($this->response instanceof MatchingResponse) {
            if (count($this->options) < 2) {
                throw InvalidQuestion::create();
            }
            $leftIds = [];
            $rightIds = [];
            foreach ($this->options as $option) {
                if ($option->side() === 'left') {
                    $leftIds[] = $option->refId();

                    continue;
                }
                if ($option->side() === 'right') {
                    $rightIds[] = $option->refId();

                    continue;
                }
                throw InvalidQuestion::create();
            }
            foreach ($this->response->pairs as $pair) {
                self::assertRefExists($leftIds, $pair['leftId']);
                self::assertRefExists($rightIds, $pair['rightId']);
            }

            return;
        }

        throw InvalidQuestion::create();
    }

    /** @param list<string> $available */
    private static function assertRefExists(array $available, string $refId): void
    {
        if (! in_array($refId, $available, true)) {
            throw InvalidQuestion::create();
        }
    }

    private static function optionalString(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (strlen($value) > $maxLength) {
            throw InvalidQuestion::create();
        }

        return $value;
    }
}