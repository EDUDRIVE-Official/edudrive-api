<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Identity\Application\Queries\GetMyStudentProfileQuery;
use Modules\Identity\Application\Responses\MyStudentProfileResponse;
use Modules\Identity\Application\Services\StudentProfileComposer;

final readonly class GetMyStudentProfileHandler
{
    public function __construct(
        private StudentProfileComposer $composer,
    ) {}

    public function handle(GetMyStudentProfileQuery $query): MyStudentProfileResponse
    {
        return $this->composer->compose($query->userId);
    }
}
