<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum CompetencyCategory: string
{
    case RiskManagement = 'risk_management';
    case RoadRules = 'road_rules';
    case VehicleControl = 'vehicle_control';
    case VulnerableRoadUsers = 'vulnerable_road_users';
    case EcoDriving = 'eco_driving';
}
