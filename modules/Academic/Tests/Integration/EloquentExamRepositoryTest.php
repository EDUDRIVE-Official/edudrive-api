<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\LicenseCategory;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamQuestionModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentExamRepository;

/** @return list<ExamQuestion> */
function examRepoQuestions(array $questionIds): array
{
    return array_map(
        static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 1),
        $questionIds,
        array_keys($questionIds),
    );
}

/** Persists a course and a question for exam repository tests, returning [courseId, questionIds]. */
function examRepoFixtures(): array
{
    $courseRepository = app(CourseRepository::class);
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EXR-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso de examen'),
    );
    $courseRepository->save($course);

    $questionRepository = app(QuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $questionIds = [];
    foreach (['opt-a', 'opt-b'] as $refId) {
        $question = Question::create(
            QuestionId::fromString((string) Str::uuid()),
            QuestionType::SingleChoice,
            $competencyId,
            '¿Pregunta '.$refId.'?',
            1,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $refId]),
            [
                QuestionOption::create($refId, QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
                QuestionOption::create('opt-x', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            ],
        );
        $questionRepository->save($question);
        $questionIds[] = $question->id()->value();
    }

    return [$course->id()->value(), $questionIds];
}

it('guarda y reconstruye un examen con sus preguntas ordenadas', function (): void {
    [$courseId, $questionIds] = examRepoFixtures();
    $repository = app(EloquentExamRepository::class);

    $exam = Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString($courseId),
        title: 'Examen integración',
        description: 'Descripción.',
        durationMinutes: 30,
        maxAttempts: 2,
        passingScore: 75,
        shuffleQuestions: true,
        feedbackMode: ExamFeedbackMode::AfterSubmission,
        kind: ExamKind::Theory,
        licenseCategory: LicenseCategory::fromString('B1'),
        allowPartialCredit: true,
        applyPenalties: true,
        questions: examRepoQuestions($questionIds),
    );
    $repository->save($exam);

    $stored = $repository->findById($exam->id());

    expect($stored)->not->toBeNull()
        ->and($stored?->title())->toBe('Examen integración')
        ->and($stored?->maxAttempts())->toBe(2)
        ->and($stored?->passingScore())->toBe(75)
        ->and($stored?->shuffleQuestions())->toBeTrue()
        ->and($stored?->feedbackMode())->toBe(ExamFeedbackMode::AfterSubmission)
        ->and($stored?->kind())->toBe(ExamKind::Theory)
        ->and($stored?->licenseCategory()?->value())->toBe('B1')
        ->and($stored?->allowPartialCredit())->toBeTrue()
        ->and($stored?->applyPenalties())->toBeTrue()
        ->and($stored?->questions())->toHaveCount(2)
        ->and($stored?->questions()[0]->position())->toBe(1)
        ->and($stored?->questions()[1]->position())->toBe(2);
});

it('lista exámenes filtrados por curso', function (): void {
    [$courseId, $questionIds] = examRepoFixtures();
    $repository = app(EloquentExamRepository::class);
    $otherCourse = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EXR-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Otro curso'),
    );
    app(CourseRepository::class)->save($otherCourse);

    $repository->save(Exam::create(ExamId::fromString((string) Str::uuid()), CourseId::fromString($courseId), 'Del curso', examRepoQuestions($questionIds)));
    $repository->save(Exam::create(ExamId::fromString((string) Str::uuid()), $otherCourse->id(), 'De otro curso', examRepoQuestions($questionIds)));

    $all = $repository->all();
    expect($all)->toHaveCount(2);

    $filtered = $repository->all(CourseId::fromString($courseId));
    expect($filtered)->toHaveCount(1)
        ->and($filtered[0]->title())->toBe('Del curso');
});

it('borra un examen y sus preguntas asociadas', function (): void {
    [$courseId, $questionIds] = examRepoFixtures();
    $repository = app(EloquentExamRepository::class);
    $exam = Exam::create(ExamId::fromString((string) Str::uuid()), CourseId::fromString($courseId), 'A borrar', examRepoQuestions($questionIds));
    $repository->save($exam);

    $repository->delete($exam->id());

    expect($repository->findById($exam->id()))->toBeNull()
        ->and(ExamQuestionModel::query()->where('exam_id', $exam->id()->value())->count())->toBe(0);
});

it('reemplaza las preguntas al guardar un examen existente', function (): void {
    [$courseId, $questionIds] = examRepoFixtures();
    $repository = app(EloquentExamRepository::class);
    $exam = Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString($courseId),
        title: 'Original',
        questions: examRepoQuestions([$questionIds[0]]),
    );
    $repository->save($exam);

    $exam->replace(
        title: 'Actualizado',
        questions: examRepoQuestions([$questionIds[0], $questionIds[1]]),
    );
    $repository->save($exam);

    $stored = $repository->findById($exam->id());
    expect($stored)->not->toBeNull()
        ->and($stored?->id()->value())->toBe($exam->id()->value())
        ->and($stored?->title())->toBe('Actualizado')
        ->and($stored?->questions())->toHaveCount(2);

    $examId = $exam->id()->value();
    $rows = ExamQuestionModel::query()->where('exam_id', $examId)->get();
    expect($rows)->toHaveCount(2);
});

it('reconstruye valores nulos y los completos del examen', function (): void {
    [$courseId, $questionIds] = examRepoFixtures();
    $repository = app(EloquentExamRepository::class);

    $exam = Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString($courseId),
        title: 'Sin descripción',
        questions: examRepoQuestions($questionIds),
    );
    $repository->save($exam);

    $stored = $repository->findById($exam->id());
    expect($stored)->not->toBeNull()
        ->and($stored?->description())->toBeNull()
        ->and($stored?->durationMinutes())->toBeNull()
        ->and($stored?->maxAttempts())->toBe(1)
        ->and($stored?->passingScore())->toBe(60)
        ->and($stored?->shuffleQuestions())->toBeFalse()
        ->and($stored?->feedbackMode())->toBe(ExamFeedbackMode::None);
});
