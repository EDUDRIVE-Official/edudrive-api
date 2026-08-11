<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\ListQuestionsQuery;
use Modules\Academic\Application\Responses\QuestionListItemResponse;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

final readonly class ListQuestionsHandler
{
    public function __construct(private QuestionRepository $questions) {}

    /** @return list<QuestionListItemResponse> */
    public function handle(ListQuestionsQuery $query): array
    {
        $competencyId = $query->competencyId === null ? null : CompetencyId::fromString($query->competencyId);

        return array_map(
            static fn (Question $question): QuestionListItemResponse => QuestionListItemResponse::fromQuestion($question),
            $this->questions->all($competencyId),
        );
    }
}
