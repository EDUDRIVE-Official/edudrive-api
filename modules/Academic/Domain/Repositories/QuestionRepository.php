<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

interface QuestionRepository
{
    public function save(Question $question): void;

    public function findById(QuestionId $id): ?Question;

    /** @return list<Question> */
    public function all(?CompetencyId $competencyId = null): array;

    public function delete(QuestionId $id): void;
}