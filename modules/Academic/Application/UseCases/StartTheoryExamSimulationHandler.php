<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Commands\StartTheoryExamSimulationCommand;
use Modules\Academic\Application\Exceptions\InvalidTheoryExam;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\ValueObjects\ExamId;

final readonly class StartTheoryExamSimulationHandler
{
    public function __construct(
        private ExamRepository $exams,
        private StartExamAttemptHandler $startAttempts,
    ) {}

    public function handle(StartTheoryExamSimulationCommand $command): ExamAttemptResponse
    {
        $exam = $this->exams->findById(ExamId::fromString($command->examId));
        if ($exam === null || $exam->kind() !== ExamKind::Theory) {
            throw InvalidTheoryExam::create();
        }

        return $this->startAttempts->handle(new StartExamAttemptCommand($command->examId, $command->userId));
    }
}
