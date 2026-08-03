<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\ListCompetenciesQuery;
use Modules\Academic\Application\Responses\CompetencyResponse;
use Modules\Academic\Domain\Aggregates\Competency;
use Modules\Academic\Domain\Repositories\CompetencyRepository;

final readonly class ListCompetenciesHandler
{
    public function __construct(private CompetencyRepository $competencies) {}
    /** @return list<CompetencyResponse> */
    public function handle(ListCompetenciesQuery $query): array
    {
        return array_map(static fn (Competency $competency): CompetencyResponse => CompetencyResponse::fromCompetency($competency), $this->competencies->all());
    }
}
