<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Exceptions\InvalidExam;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

/** @return list<ExamQuestion> */
function examQuestions(array $ids): array
{
    return array_map(
        static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 1),
        $ids,
        array_keys($ids),
    );
}

it('crea un examen con su configuración y preguntas', function (): void {
    $exam = Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Examen teórico B1',
        description: 'Evaluación final del curso.',
        durationMinutes: 45,
        maxAttempts: 2,
        passingScore: 70,
        shuffleQuestions: true,
        feedbackMode: ExamFeedbackMode::AfterSubmission,
        questions: examQuestions([(string) Str::uuid(), (string) Str::uuid()]),
    );

    expect($exam->title())->toBe('Examen teórico B1')
        ->and($exam->maxAttempts())->toBe(2)
        ->and($exam->passingScore())->toBe(70)
        ->and($exam->shuffleQuestions())->toBeTrue()
        ->and($exam->feedbackMode())->toBe(ExamFeedbackMode::AfterSubmission)
        ->and($exam->questions())->toHaveCount(2);
});

it('rechaza un examen sin preguntas', function (): void {
    expect(fn () => Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Examen vacío',
        questions: [],
    ))->toThrow(InvalidExam::class);
});

it('rechaza un examen con preguntas duplicadas', function (): void {
    $questionId = (string) Str::uuid();
    expect(fn () => Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Examen duplicado',
        questions: examQuestions([$questionId, $questionId]),
    ))->toThrow(InvalidExam::class);
});

it('rechaza un título vacío', function (): void {
    expect(fn () => Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: '   ',
        questions: examQuestions([(string) Str::uuid()]),
    ))->toThrow(InvalidExam::class);
});

it('rechaza un passing score fuera de rango', function (): void {
    expect(fn () => Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Examen',
        passingScore: 0,
        questions: examQuestions([(string) Str::uuid()]),
    ))->toThrow(InvalidExam::class);
});

it('rechaza intentos en cero', function (): void {
    expect(fn () => Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Examen',
        maxAttempts: 0,
        questions: examQuestions([(string) Str::uuid()]),
    ))->toThrow(InvalidExam::class);
});

it('rechaza una duración en cero', function (): void {
    expect(fn () => Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Examen',
        durationMinutes: 0,
        questions: examQuestions([(string) Str::uuid()]),
    ))->toThrow(InvalidExam::class);
});

it('reemplaza los datos del examen conservando curso e id', function (): void {
    $id = ExamId::fromString((string) Str::uuid());
    $courseId = CourseId::fromString((string) Str::uuid());
    $exam = Exam::create(
        id: $id,
        courseId: $courseId,
        title: 'Original',
        questions: examQuestions([(string) Str::uuid()]),
    );

    $exam->replace(
        title: 'Actualizado',
        description: null,
        durationMinutes: null,
        maxAttempts: 3,
        passingScore: 80,
        shuffleQuestions: false,
        feedbackMode: ExamFeedbackMode::None,
        questions: examQuestions([(string) Str::uuid(), (string) Str::uuid()]),
    );

    expect($exam->id())->toBe($id)
        ->and($exam->courseId())->toBe($courseId)
        ->and($exam->title())->toBe('Actualizado')
        ->and($exam->maxAttempts())->toBe(3)
        ->and($exam->questions())->toHaveCount(2);
});
