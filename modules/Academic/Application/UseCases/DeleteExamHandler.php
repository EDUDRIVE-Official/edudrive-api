<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\DeleteExamCommand;
use Modules\Academic\Application\Exceptions\ExamNotFound;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\ValueObjects\ExamId;

final readonly class DeleteExamHandler
{
    public function __construct(private ExamRepository $exams) {}

    public function handle(DeleteExamCommand $command): void
    {
        $id = ExamId::fromString($command->examId);
        if ($this->exams->findById($id) === null) {
            throw ExamNotFound::withId($command->examId);
        }

        $this->exams->delete($id);
    }
}
