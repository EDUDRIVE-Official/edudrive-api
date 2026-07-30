<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case InstitutionalAdmin = 'institutional_admin';
    case Teacher = 'teacher';
    case Student = 'student';
}
