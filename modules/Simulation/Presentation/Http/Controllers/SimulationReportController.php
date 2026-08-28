<?php

declare(strict_types=1);

namespace Modules\Simulation\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Simulation\Application\Queries\GetUserEvolutionReportQuery;
use Modules\Simulation\Application\Queries\GetUserRiskReportQuery;
use Modules\Simulation\Application\Queries\GetUserSessionsReportQuery;
use Modules\Simulation\Application\Queries\GetUserTelemetryReportQuery;
use Modules\Simulation\Application\Responses\UserEvolutionReportResponse;
use Modules\Simulation\Application\Responses\UserRiskReportResponse;
use Modules\Simulation\Application\Responses\UserSessionsReportResponse;
use Modules\Simulation\Application\Responses\UserTelemetryReportResponse;

final class SimulationReportController
{
    public function sessions(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetUserSessionsReportQuery(userIds: self::userIds($request)));
        assert(is_array($result));

        /** @var list<UserSessionsReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (UserSessionsReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    public function telemetry(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetUserTelemetryReportQuery(userIds: self::userIds($request)));
        assert(is_array($result));

        /** @var list<UserTelemetryReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (UserTelemetryReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    public function evolution(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetUserEvolutionReportQuery(userIds: self::userIds($request)));
        assert(is_array($result));

        /** @var list<UserEvolutionReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (UserEvolutionReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    public function risks(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetUserRiskReportQuery(userIds: self::userIds($request)));
        assert(is_array($result));

        /** @var list<UserRiskReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (UserRiskReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    /** @return list<string> */
    private static function userIds(Request $request): array
    {
        $userIds = $request->query('user_ids', []);

        if (! is_array($userIds)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $userId): string => (string) $userId, $userIds));
    }
}
