<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Application\Services\QuestionResponseFactory;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\AttemptQuestionGrade;
use Modules\Academic\Domain\Entities\CompetencyGrade;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamAttemptModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamAttemptQuestionModel;

final readonly class EloquentExamAttemptRepository implements ExamAttemptRepository
{
    public function save(ExamAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt): void {
            $model = ExamAttemptModel::query()->updateOrCreate(
                ['id' => $attempt->id()->value()],
                [
                    'exam_id' => $attempt->examId()->value(),
                    'user_id' => $attempt->userId(),
                    'status' => $attempt->status()->value,
                    'started_at' => $attempt->startedAt(),
                    'submitted_at' => $attempt->submittedAt(),
                    'title' => $attempt->title(),
                    'duration_minutes' => $attempt->durationMinutes(),
                    'passing_score' => $attempt->passingScore(),
                    'shuffle_questions' => $attempt->shuffleQuestions(),
                    'feedback_mode' => $attempt->feedbackMode()->value,
                    'score' => $attempt->score(),
                    'total_points' => $attempt->totalPoints(),
                    'percentage' => $attempt->percentage(),
                    'passed' => $attempt->passed(),
                    'grading_breakdown' => $this->serializeQuestionBreakdown($attempt),
                    'competency_results' => $this->serializeCompetencyBreakdown($attempt),
                ],
            );

            $model->questions()->delete();

            foreach ($attempt->questions() as $question) {
                ExamAttemptQuestionModel::query()->create([
                    'id' => $question->id()->value(),
                    'attempt_id' => $model->id,
                    'position' => $question->position(),
                    'question_id' => $question->questionId()->value(),
                    'competency_id' => $question->competencyId()->value(),
                    'points' => $question->points(),
                    'prompt' => $question->prompt(),
                    'type' => $question->type()->value,
                    'options' => $question->options(),
                    'correct_response' => $question->correctResponse()->toArray(),
                    'explanation' => $question->explanation(),
                    'user_response' => $question->userResponse()?->toArray(),
                    'is_correct' => $question->isCorrect(),
                    'answered_at' => $question->answeredAt(),
                ]);
            }
        });
    }

    public function findById(ExamAttemptId $id): ?ExamAttempt
    {
        $model = ExamAttemptModel::query()->with('questions')->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findActiveFor(ExamId $examId, string $userId): ?ExamAttempt
    {
        $model = ExamAttemptModel::query()->with('questions')
            ->where('exam_id', $examId->value())
            ->where('user_id', $userId)
            ->where('status', ExamAttemptStatus::InProgress->value)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function countCompletedFor(ExamId $examId, string $userId): int
    {
        return ExamAttemptModel::query()
            ->where('exam_id', $examId->value())
            ->where('user_id', $userId)
            ->where('status', '!=', ExamAttemptStatus::InProgress->value)
            ->count();
    }

    /** @return list<ExamAttempt> */
    public function all(?ExamId $examId = null, ?string $userId = null, ?ExamAttemptStatus $status = null): array
    {
        $builder = ExamAttemptModel::query()->with('questions');
        if ($examId !== null) {
            $builder->where('exam_id', $examId->value());
        }
        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }
        if ($status !== null) {
            $builder->where('status', $status->value);
        }

        return array_values(
            $builder->orderBy('created_at')->get()
                ->map(fn (ExamAttemptModel $model): ExamAttempt => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(ExamAttemptModel $model): ExamAttempt
    {
        /** @var list<ExamAttemptQuestionModel> $questionModels */
        $questionModels = array_values($model->questions->all());

        $legacyCompetencyMap = $this->legacyCompetencyMap($questionModels);

        $questions = array_values($model->questions->map(
            fn (ExamAttemptQuestionModel $question): AttemptQuestion => $this->toAttemptQuestion($question, $legacyCompetencyMap)
        )->all());

        return ExamAttempt::restore(
            ExamAttemptId::fromString((string) $model->getAttribute('id')),
            ExamId::fromString((string) $model->getAttribute('exam_id')),
            (string) $model->getAttribute('user_id'),
            ExamAttemptStatus::from((string) $model->getAttribute('status')),
            new DateTimeImmutable((string) $model->getAttribute('started_at')),
            $model->getAttribute('submitted_at') === null ? null : new DateTimeImmutable((string) $model->getAttribute('submitted_at')),
            (string) $model->getAttribute('title'),
            $model->getAttribute('duration_minutes') === null ? null : (int) $model->getAttribute('duration_minutes'),
            (int) $model->getAttribute('passing_score'),
            (bool) $model->getAttribute('shuffle_questions'),
            ExamFeedbackMode::from((string) $model->getAttribute('feedback_mode')),
            $questions,
            (int) $model->getAttribute('score'),
            (int) $model->getAttribute('total_points'),
            (int) $model->getAttribute('percentage'),
            (bool) $model->getAttribute('passed'),
            $this->rehydrateQuestionBreakdown($model->getAttribute('grading_breakdown')),
            $this->rehydrateCompetencyBreakdown($model->getAttribute('competency_results')),
        );
    }

    /** @return list<array<string, string|int|bool>>|null */
    private function serializeQuestionBreakdown(ExamAttempt $attempt): ?array
    {
        $breakdown = $attempt->questionBreakdown();

        if ($breakdown === []) {
            return null;
        }

        return array_map(
            static fn (AttemptQuestionGrade $grade): array => $grade->toArray(),
            $breakdown,
        );
    }

    /** @return list<array<string, string|int>>|null */
    private function serializeCompetencyBreakdown(ExamAttempt $attempt): ?array
    {
        $breakdown = $attempt->competencyBreakdown();

        if ($breakdown === []) {
            return null;
        }

        return array_map(
            static fn (CompetencyGrade $grade): array => $grade->toArray(),
            $breakdown,
        );
    }

    /**
     *  @return list<AttemptQuestionGrade> */
    private function rehydrateQuestionBreakdown(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        return array_values(array_map(
            static function (mixed $grade): AttemptQuestionGrade {
                if (! is_array($grade)) {
                    throw InvalidExamAttempt::create();
                }

                return new AttemptQuestionGrade(
                    AttemptQuestionId::fromString((string) ($grade['attempt_question_id'] ?? '')),
                    QuestionId::fromString((string) ($grade['question_id'] ?? '')),
                    CompetencyId::fromString((string) ($grade['competency_id'] ?? '')),
                    (int) ($grade['score'] ?? 0),
                    (int) ($grade['total_points'] ?? 0),
                    (int) ($grade['percentage'] ?? 0),
                    (bool) ($grade['is_correct'] ?? false),
                    (bool) ($grade['is_answered'] ?? false),
                );
            },
            $payload,
        ));
    }

    /**
     *  @return list<CompetencyGrade> */
    private function rehydrateCompetencyBreakdown(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        return array_values(array_map(
            static function (mixed $grade): CompetencyGrade {
                if (! is_array($grade)) {
                    throw InvalidExamAttempt::create();
                }

                return new CompetencyGrade(
                    CompetencyId::fromString((string) ($grade['competency_id'] ?? '')),
                    (int) ($grade['score'] ?? 0),
                    (int) ($grade['total_points'] ?? 0),
                    (int) ($grade['percentage'] ?? 0),
                );
            },
            $payload,
        ));
    }

    /** @param array<string, string> $legacyCompetencyMap */
    private function toAttemptQuestion(ExamAttemptQuestionModel $model, array $legacyCompetencyMap): AttemptQuestion
    {
        /** @var array<string, mixed>|null $userResponse */
        $userResponse = $model->getAttribute('user_response');

        /** @var list<array<string, mixed>> $options */
        $options = $model->getAttribute('options') ?? [];

        /** @var array<string, mixed> $correctResponse */
        $correctResponse = $model->getAttribute('correct_response');

        $competencyId = $model->getAttribute('competency_id');
        if (! is_string($competencyId) || trim($competencyId) === '') {
            $competencyId = $legacyCompetencyMap[(string) $model->getAttribute('question_id')] ?? null;
        }

        if (! is_string($competencyId) || trim($competencyId) === '') {
            throw InvalidExamAttempt::create();
        }

        return AttemptQuestion::restore(
            AttemptQuestionId::fromString((string) $model->getAttribute('id')),
            (int) $model->getAttribute('position'),
            QuestionId::fromString((string) $model->getAttribute('question_id')),
            CompetencyId::fromString((string) $competencyId),
            (int) $model->getAttribute('points'),
            (string) $model->getAttribute('prompt'),
            QuestionType::from((string) $model->getAttribute('type')),
            $options,
            QuestionResponseFactory::fromPayload((string) $model->getAttribute('type'), $correctResponse),
            $model->getAttribute('explanation') === null ? null : (string) $model->getAttribute('explanation'),
            $userResponse === null ? null : QuestionResponseFactory::fromPayload((string) $model->getAttribute('type'), $userResponse),
            $model->getAttribute('is_correct') === null ? null : (bool) $model->getAttribute('is_correct'),
            $model->getAttribute('answered_at') === null ? null : new DateTimeImmutable((string) $model->getAttribute('answered_at')),
        );
    }

    /** @param list<ExamAttemptQuestionModel> $questions
     *  @return array<string, string> */
    private function legacyCompetencyMap(array $questions): array
    {
        $questionIdsNeedingFallback = array_values(array_unique(array_filter(
            array_map(function (ExamAttemptQuestionModel $question): ?string {
                $competencyId = $question->getAttribute('competency_id');
                if (is_string($competencyId) && trim($competencyId) !== '') {
                    return null;
                }

                return (string) $question->getAttribute('question_id');
            }, $questions),
            static fn (?string $questionId): bool => $questionId !== null && $questionId !== '',
        )));

        if ($questionIdsNeedingFallback === []) {
            return [];
        }

        /** @var array<string, string> $map */
        $map = DB::table('academic_questions')
            ->whereIn('id', $questionIdsNeedingFallback)
            ->pluck('competency_id', 'id')
            ->filter(static fn (mixed $competencyId): bool => is_string($competencyId) && trim($competencyId) !== '')
            ->mapWithKeys(static fn (mixed $competencyId, mixed $questionId): array => [(string) $questionId => (string) $competencyId])
            ->all();

        return $map;
    }
}
