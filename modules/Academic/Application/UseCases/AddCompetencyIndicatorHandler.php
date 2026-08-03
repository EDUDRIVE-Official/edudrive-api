<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\AddCompetencyIndicatorCommand;
use Modules\Academic\Application\Exceptions\CompetencyNotFound;
use Modules\Academic\Application\Responses\CompetencyResponse;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

final readonly class AddCompetencyIndicatorHandler
{
    public function __construct(private CompetencyRepository $competencies) {}

    public function handle(AddCompetencyIndicatorCommand $command): CompetencyResponse
    {
        $id = CompetencyId::fromString($command->competencyId);
        $competency = $this->competencies->findById($id) ?? throw CompetencyNotFound::forId($id);
        $competency->addIndicator($command->subcompetencyCode, $command->code, $command->description);
        $this->competencies->save($competency);

        return CompetencyResponse::fromCompetency($competency);
    }
}
