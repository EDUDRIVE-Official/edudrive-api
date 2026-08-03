<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\ArchiveProgramCommand;
use Modules\Academic\Application\Exceptions\ProgramNotFound;
use Modules\Academic\Application\Responses\ProgramResponse;
use Modules\Academic\Domain\Repositories\ProgramRepository;
use Modules\Academic\Domain\ValueObjects\ProgramId;

final readonly class ArchiveProgramHandler
{
    public function __construct(
        private ProgramRepository $programs,
    ) {}

    public function handle(ArchiveProgramCommand $command): ProgramResponse
    {
        $program = $this->programs->findById(ProgramId::fromString($command->programId));

        if ($program === null) {
            throw ProgramNotFound::withId($command->programId);
        }

        $program->archive(new DateTimeImmutable);
        $this->programs->save($program);

        return ProgramResponse::fromProgram($program);
    }
}
