<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Domain\Enums;

enum RoadPassportHistoryType: string
{
    case StatusChanged = 'status_changed';
    case LevelChanged = 'level_changed';
}
