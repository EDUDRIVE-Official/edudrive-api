<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Enums;

enum OrganizationType: string
{
    case EducationalCenter = 'educational_center';
    case DrivingSchool = 'driving_school';
    case Company = 'company';
    case PublicInstitution = 'public_institution';
    case Other = 'other';
}
