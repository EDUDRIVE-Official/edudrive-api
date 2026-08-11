<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamQuestionModel;

final readonly class EloquentExamRepository implements ExamRepository
{
    public function save(Exam $exam): void
    {
        DB::transaction(function () use ($exam): void {
            $model = ExamModel::query()->updateOrCreate(
                ['id' => $exam->id()->value()],
                [
                    'course_id' => $exam->courseId()->value(),
                    'title' => $exam->title(),
                    'description' => $exam->description(),
                    'duration_minutes' => $exam->durationMinutes(),
                    'max_attempts' => $exam->maxAttempts(),
                    'passing_score' => $exam->passingScore(),
                    'shuffle_questions' => $exam->shuffleQuestions(),
                    'feedback_mode' => $exam->feedbackMode()->value,
                ],
            );

            $model->questions()->delete();

            foreach ($exam->questions() as $question) {
                ExamQuestionModel::query()->create([
                    'id' => (string) Str::uuid(),
                    'exam_id' => $model->id,
                    'question_id' => $question->questionId()->value(),
                    'position' => $question->position(),
                    'points' => $question->points(),
                ]);
            }
        });
    }

    public function findById(ExamId $id): ?Exam
    {
        $model = ExamModel::query()->with('questions')->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<Exam> */
    public function all(?CourseId $courseId = null): array
    {
        $builder = ExamModel::query()->with('questions');
        if ($courseId !== null) {
            $builder->where('course_id', $courseId->value());
        }

        return array_values(
            $builder->orderBy('created_at')->get()
                ->map(fn (ExamModel $model): Exam => $this->toDomain($model))
                ->all(),
        );
    }

    public function delete(ExamId $id): void
    {
        ExamModel::query()->where('id', $id->value())->delete();
    }

    private function toDomain(ExamModel $model): Exam
    {
        $questions = array_values($model->questions->map(fn (ExamQuestionModel $question): ExamQuestion => ExamQuestion::create(
            (int) $question->getAttribute('position'),
            QuestionId::fromString((string) $question->getAttribute('question_id')),
            (int) $question->getAttribute('points'),
        ))->all());

        return Exam::restore(
            ExamId::fromString((string) $model->getAttribute('id')),
            CourseId::fromString((string) $model->getAttribute('course_id')),
            (string) $model->getAttribute('title'),
            $questions,
            $model->getAttribute('description') === null ? null : (string) $model->getAttribute('description'),
            $model->getAttribute('duration_minutes') === null ? null : (int) $model->getAttribute('duration_minutes'),
            (int) $model->getAttribute('max_attempts'),
            (int) $model->getAttribute('passing_score'),
            (bool) $model->getAttribute('shuffle_questions'),
            ExamFeedbackMode::from((string) $model->getAttribute('feedback_mode')),
        );
    }
}
