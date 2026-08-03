<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\AddSubcompetencyCommand;
use Modules\Academic\Application\Exceptions\CompetencyNotFound;
use Modules\Academic\Application\Responses\CompetencyResponse;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

final readonly class AddSubcompetencyHandler
{
    public function __construct(private CompetencyRepository $competencies) {}

    public function handle(AddSubcompetencyCommand $command): CompetencyResponse
    {
        $id = CompetencyId::fromString($command->competencyId);
        $competency = $this->competencies->findById($id) ?? throw CompetencyNotFound::forId($id);
        $competency->addSubcompetency($command->code, $command->title);
        $this->competencies->save($competency);

        return CompetencyResponse::fromCompetency($competency);
    }
}
