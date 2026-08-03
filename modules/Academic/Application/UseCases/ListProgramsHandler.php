<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\ListProgramsQuery;
use Modules\Academic\Application\Responses\ProgramResponse;
use Modules\Academic\Domain\Aggregates\EducationalProgram;
use Modules\Academic\Domain\Repositories\ProgramRepository;

final readonly class ListProgramsHandler
{
    public function __construct(private ProgramRepository $programs) {}

    /** @return list<ProgramResponse> */
    public function handle(ListProgramsQuery $query): array
    {
        return array_map(
            static fn (EducationalProgram $program): ProgramResponse => ProgramResponse::fromProgram($program),
            $this->programs->all(),
        );
    }
}
