<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\DeleteQuestionCommand;
use Modules\Academic\Application\Exceptions\QuestionNotFound;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\QuestionId;

final readonly class DeleteQuestionHandler
{
    public function __construct(private QuestionRepository $questions) {}

    public function handle(DeleteQuestionCommand $command): void
    {
        $id = QuestionId::fromString($command->questionId);
        if ($this->questions->findById($id) === null) {
            throw QuestionNotFound::withId($command->questionId);
        }

        $this->questions->delete($id);
    }
}
