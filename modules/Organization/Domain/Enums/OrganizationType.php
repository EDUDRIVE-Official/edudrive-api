<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Enums;

enum OrganizationType: string
{
    case EducationalCenter = 'educational_center';
    case DrivingSchool = 'driving_school';
    case Company = 'company';
    case PublicInstitution = 'public_institution';
    case University = 'university';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EducationalCenter => 'Centro educativo',
            self::DrivingSchool => 'Escuela de manejo',
            self::Company => 'Empresa',
            self::PublicInstitution => 'Institución pública',
            self::University => 'Universidad',
            self::Other => 'Otro',
        };
    }
}
