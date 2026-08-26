<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use Modules\Academic\Domain\Entities\Responses\QuestionResponse;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

final class AttemptQuestion
{
    /** @param  list<array<string, mixed>>  $options */
    private function __construct(
        private AttemptQuestionId $id,
        private int $position,
        private QuestionId $questionId,
        private CompetencyId $competencyId,
        private int $points,
        private string $prompt,
        private QuestionType $type,
        private array $options,
        private QuestionResponse $correctResponse,
        private ?string $explanation,
        private ?QuestionResponse $userResponse,
        private ?bool $isCorrect,
        private ?\DateTimeImmutable $answeredAt,
    ) {}

    /** @param  list<array<string, mixed>>  $options */
    public static function create(
        AttemptQuestionId $id,
        int $position,
        QuestionId $questionId,
        CompetencyId $competencyId,
        int $points,
        string $prompt,
        QuestionType $type,
        array $options,
        QuestionResponse $correctResponse,
        ?string $explanation = null,
        ?QuestionResponse $userResponse = null,
        ?bool $isCorrect = null,
        ?\DateTimeImmutable $answeredAt = null,
    ): self {
        if ($position < 1 || $points < 1 || trim($prompt) === '') {
            throw InvalidExamAttempt::create();
        }

        return new self($id, $position, $questionId, $competencyId, $points, trim($prompt), $type, $options, $correctResponse, $explanation, $userResponse, $isCorrect, $answeredAt);
    }

    /** @param  list<array<string, mixed>>  $options */
    public static function restore(
        AttemptQuestionId $id,
        int $position,
        QuestionId $questionId,
        CompetencyId $competencyId,
        int $points,
        string $prompt,
        QuestionType $type,
        array $options,
        QuestionResponse $correctResponse,
        ?string $explanation = null,
        ?QuestionResponse $userResponse = null,
        ?bool $isCorrect = null,
        ?\DateTimeImmutable $answeredAt = null,
    ): self {
        return self::create($id, $position, $questionId, $competencyId, $points, $prompt, $type, $options, $correctResponse, $explanation, $userResponse, $isCorrect, $answeredAt);
    }

    public function withPosition(int $position): self
    {
        if ($position < 1) {
            throw InvalidExamAttempt::create();
        }

        return new self($this->id, $position, $this->questionId, $this->competencyId, $this->points, $this->prompt, $this->type, $this->options, $this->correctResponse, $this->explanation, $this->userResponse, $this->isCorrect, $this->answeredAt);
    }

    public function answer(QuestionResponse $response, \DateTimeImmutable $answeredAt): void
    {
        $this->userResponse = $response;
        $this->isCorrect = $this->correctResponse->matches($response);
        $this->answeredAt = $answeredAt;
    }

    public function id(): AttemptQuestionId
    {
        return $this->id;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function questionId(): QuestionId
    {
        return $this->questionId;
    }

    public function competencyId(): CompetencyId
    {
        return $this->competencyId;
    }

    public function points(): int
    {
        return $this->points;
    }

    public function prompt(): string
    {
        return $this->prompt;
    }

    public function type(): QuestionType
    {
        return $this->type;
    }

    /** @return list<array<string, mixed>> */
    public function options(): array
    {
        return $this->options;
    }

    public function correctResponse(): QuestionResponse
    {
        return $this->correctResponse;
    }

    public function explanation(): ?string
    {
        return $this->explanation;
    }

    public function userResponse(): ?QuestionResponse
    {
        return $this->userResponse;
    }

    public function isCorrect(): ?bool
    {
        return $this->isCorrect;
    }

    public function answeredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function answered(): bool
    {
        return $this->userResponse !== null;
    }
}
