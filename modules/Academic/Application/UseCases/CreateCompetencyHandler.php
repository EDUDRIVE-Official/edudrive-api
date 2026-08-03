<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CreateCompetencyCommand;
use Modules\Academic\Application\Exceptions\CompetencyCodeAlreadyExists;
use Modules\Academic\Application\Responses\CompetencyResponse;
use Modules\Academic\Domain\Aggregates\Competency;
use Modules\Academic\Domain\Enums\CompetencyCategory;
use Modules\Academic\Domain\Enums\MasteryLevel;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyCode;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

final readonly class CreateCompetencyHandler
{
    public function __construct(private CompetencyRepository $competencies) {}

    public function handle(CreateCompetencyCommand $command): CompetencyResponse
    {
        $code = CompetencyCode::fromString($command->code);
        if ($this->competencies->existsByCode($code)) {
            throw CompetencyCodeAlreadyExists::forCode($code);
        }

        $competency = Competency::create(
            CompetencyId::fromString((string) Str::uuid()),
            $code,
            $command->title,
            $command->description,
            CompetencyCategory::from($command->category),
            MasteryLevel::from($command->masteryLevel),
        );
        $this->competencies->save($competency);

        return CompetencyResponse::fromCompetency($competency);
    }
}
