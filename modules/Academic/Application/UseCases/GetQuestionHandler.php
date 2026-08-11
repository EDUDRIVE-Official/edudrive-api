<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\QuestionNotFound;
use Modules\Academic\Application\Queries\GetQuestionQuery;
use Modules\Academic\Application\Responses\QuestionResponse;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\QuestionId;

final readonly class GetQuestionHandler
{
    public function __construct(private QuestionRepository $questions) {}

    public function handle(GetQuestionQuery $query): QuestionResponse
    {
        $question = $this->questions->findById(QuestionId::fromString($query->questionId));
        if ($question === null) {
            throw QuestionNotFound::withId($query->questionId);
        }

        return QuestionResponse::fromQuestion($question);
    }
}