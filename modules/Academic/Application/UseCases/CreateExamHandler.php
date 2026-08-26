<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CreateExamCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Exceptions\InvalidTheoryExam;
use Modules\Academic\Application\Exceptions\QuestionNotFound;
use Modules\Academic\Application\Responses\ExamResponse;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Enums\QuestionSourceKind;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\LicenseCategory;
use Modules\Academic\Domain\ValueObjects\QuestionId;

final readonly class CreateExamHandler
{
    public function __construct(
        private ExamRepository $exams,
        private CourseRepository $courses,
        private QuestionRepository $questions,
    ) {}

    public function handle(CreateExamCommand $command): ExamResponse
    {
        $courseId = CourseId::fromString($command->courseId);
        if ($this->courses->findById($courseId) === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        [$examQuestions, $questionDetails] = $this->buildExamQuestions(
            $command->questions,
            $command->kind,
            $command->licenseCategory,
        );
        $exam = Exam::create(
            ExamId::fromString((string) Str::uuid()),
            $courseId,
            $command->title,
            $examQuestions,
            $command->description,
            $command->durationMinutes,
            $command->maxAttempts,
            $command->passingScore,
            $command->shuffleQuestions,
            ExamFeedbackMode::from($command->feedbackMode),
            ExamKind::from($command->kind),
            $command->licenseCategory === null ? null : LicenseCategory::fromString($command->licenseCategory),
            $command->allowPartialCredit,
            $command->applyPenalties,
        );
        $this->exams->save($exam);

        return ExamResponse::fromExam($exam, $questionDetails);
    }

    /**
     * @param  list<array{questionId: string, points: int}>  $payloads
     * @return array{0: list<ExamQuestion>, 1: array<string, array{refId: string, type: string}>}
     */
    private function buildExamQuestions(array $payloads, string $kind, ?string $licenseCategory): array
    {
        $examQuestions = [];
        $details = [];
        foreach ($payloads as $index => $payload) {
            $questionId = QuestionId::fromString((string) $payload['questionId']);
            $question = $this->questions->findById($questionId);
            if ($question === null) {
                throw QuestionNotFound::withId((string) $payload['questionId']);
            }
            $this->assertQuestionAllowedForExam($kind, $licenseCategory, $question);
            $details[$questionId->value()] = [
                'refId' => $question->id()->value(),
                'type' => $question->type()->value,
            ];
            $examQuestions[] = ExamQuestion::create($index + 1, $questionId, (int) $payload['points']);
        }

        return [$examQuestions, $details];
    }

    private function assertQuestionAllowedForExam(string $kind, ?string $licenseCategory, Question $question): void
    {
        if ($kind !== ExamKind::Theory->value) {
            return;
        }

        if ($question->sourceKind() !== QuestionSourceKind::Official) {
            throw InvalidTheoryExam::create();
        }

        $normalizedCategory = $licenseCategory === null ? null : LicenseCategory::fromString($licenseCategory);
        if ($normalizedCategory === null) {
            throw InvalidTheoryExam::create();
        }

        foreach ($question->licenseCategories() as $category) {
            if ($category->equals($normalizedCategory)) {
                return;
            }
        }

        throw InvalidTheoryExam::create();
    }
}
