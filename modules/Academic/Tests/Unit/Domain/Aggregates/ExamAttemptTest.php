<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

function attemptQuestions(): array
{
    return [
        AttemptQuestion::create(
            AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
            1,
            QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
            10,
            '¿Primera?',
            QuestionType::SingleChoice,
            [],
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        ),
        AttemptQuestion::create(
            AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92103'),
            2,
            QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92104'),
            10,
            '¿Segunda?',
            QuestionType::SingleChoice,
            [],
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-b']),
        ),
    ];
}

it('inicia un intento en estado in_progress con su snapshot', function (): void {
    $startedAt = new DateTimeImmutable('2026-08-12 10:00:00');
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen final',
        45,
        70,
        false,
        ExamFeedbackMode::AfterSubmission,
        attemptQuestions(),
        $startedAt,
    );

    expect($attempt->id()->value())->toBe('01981a64-8300-7b1d-b442-764ea7f92105')
        ->and($attempt->status())->toBe(ExamAttemptStatus::InProgress)
        ->and($attempt->startedAt())->toBe($startedAt)
        ->and($attempt->submittedAt())->toBeNull()
        ->and($attempt->title())->toBe('Examen final')
        ->and($attempt->durationMinutes())->toBe(45)
        ->and($attempt->passingScore())->toBe(70)
        ->and($attempt->feedbackMode())->toBe(ExamFeedbackMode::AfterSubmission)
        ->and($attempt->questions())->toHaveCount(2)
        ->and($attempt->totalPoints())->toBe(20)
        ->and($attempt->score())->toBe(0)
        ->and($attempt->percentage())->toBe(0)
        ->and($attempt->passed())->toBeFalse();
});

it('baraja el orden de las preguntas cuando shuffle_questions está activo', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        true,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
        shuffler: static fn (array $questions): array => array_reverse($questions),
    );

    $questions = $attempt->questions();
    expect($questions)->toHaveCount(2)
        ->and($questions[0]->position())->toBe(1)
        ->and($questions[1]->position())->toBe(2)
        ->and($questions[0]->questionId()->value())->toBe('01981a64-8300-7b1d-b442-764ea7f92104')
        ->and($questions[1]->questionId()->value())->toBe('01981a64-8300-7b1d-b442-764ea7f92102');
});

it('responde una pregunta y calcula el acierto', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $attempt->answer(
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        new DateTimeImmutable('2026-08-12 10:01:00'),
    );

    expect($attempt->questions()[0]->userResponse())->not->toBeNull()
        ->and($attempt->questions()[0]->isCorrect())->toBeTrue();
});

it('sobrescribe una respuesta previa mientras está in_progress', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-b']), new DateTimeImmutable('2026-08-12 10:01:00'));
    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:02:00'));

    expect($attempt->questions()[0]->isCorrect())->toBeTrue();
});

it('rechaza responder fuera de posición', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    expect(fn () => $attempt->answer(99, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:01:00')))
        ->toThrow(InvalidExamAttempt::class);
});

it('envía el intento y calcula score, percentage y passed', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:01:00'));
    $attempt->answer(2, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-b']), new DateTimeImmutable('2026-08-12 10:02:00'));

    $attempt->submit(new DateTimeImmutable('2026-08-12 10:10:00'));

    expect($attempt->status())->toBe(ExamAttemptStatus::Submitted)
        ->and($attempt->submittedAt())->not->toBeNull()
        ->and($attempt->score())->toBe(20)
        ->and($attempt->percentage())->toBe(100)
        ->and($attempt->passed())->toBeTrue();
});

it('marca como no aprobado cuando no alcanza el passing_score', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        90,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:01:00'));

    $attempt->submit(new DateTimeImmutable('2026-08-12 10:10:00'));

    expect($attempt->score())->toBe(10)
        ->and($attempt->percentage())->toBe(50)
        ->and($attempt->passed())->toBeFalse();
});

it('cancela el intento cuando se envía después del tiempo límite', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        5,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $attempt->submit(new DateTimeImmutable('2026-08-12 10:10:00'));

    expect($attempt->status())->toBe(ExamAttemptStatus::Canceled)
        ->and($attempt->score())->toBe(0);
});

it('rechaza responder o enviar un intento ya finalizado', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );
    $attempt->submit(new DateTimeImmutable('2026-08-12 10:10:00'));

    expect(fn () => $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:11:00')))
        ->toThrow(InvalidExamAttempt::class)
        ->and(fn () => $attempt->submit(new DateTimeImmutable('2026-08-12 10:12:00')))
        ->toThrow(InvalidExamAttempt::class);
});

it('cancela un intento in_progress', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $attempt->cancel();

    expect($attempt->status())->toBe(ExamAttemptStatus::Canceled);
});
