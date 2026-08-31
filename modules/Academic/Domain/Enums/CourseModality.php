<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum CourseModality: string
{
    case InPerson = 'in_person';
    case Virtual = 'virtual';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::InPerson => 'Presencial',
            self::Virtual => 'Virtual',
            self::Hybrid => 'Híbrida',
        };
    }
}
