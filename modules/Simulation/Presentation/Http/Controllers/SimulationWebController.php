<?php

declare(strict_types=1);

namespace Modules\Simulation\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Simulation\Application\Queries\GetMySimulationSessionsQuery;
use Modules\Simulation\Application\Queries\GetUserEvolutionReportQuery;
use Modules\Simulation\Application\Queries\GetUserRiskReportQuery;
use Modules\Simulation\Application\Queries\GetUserSessionsReportQuery;
use Modules\Simulation\Application\Queries\GetUserTelemetryReportQuery;
use Modules\Simulation\Application\Responses\SimulationSessionResponse;
use Modules\Simulation\Application\Responses\UserEvolutionReportResponse;
use Modules\Simulation\Application\Responses\UserRiskReportResponse;
use Modules\Simulation\Application\Responses\UserSessionsReportResponse;
use Modules\Simulation\Application\Responses\UserTelemetryReportResponse;

final class SimulationWebController
{
    public function mine(Request $request, QueryBus $queryBus): View
    {
        $sessions = $queryBus->ask(new GetMySimulationSessionsQuery(
            userId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert(is_array($sessions));

        return view('simulations.mine', [
            'sessions' => array_reverse(array_map(
                static fn (SimulationSessionResponse $session): array => $session->toArray(),
                $sessions,
            )),
        ]);
    }

    public function reports(QueryBus $queryBus): View
    {
        $sessions = $queryBus->ask(new GetUserSessionsReportQuery);
        $telemetry = $queryBus->ask(new GetUserTelemetryReportQuery);
        $evolution = $queryBus->ask(new GetUserEvolutionReportQuery);
        $risks = $queryBus->ask(new GetUserRiskReportQuery);
        assert(is_array($sessions));
        assert(is_array($telemetry));
        assert(is_array($evolution));
        assert(is_array($risks));

        $sessionReports = array_map(static fn (UserSessionsReportResponse $report): array => $report->toArray(), $sessions);
        $telemetryReports = array_map(static fn (UserTelemetryReportResponse $report): array => $report->toArray(), $telemetry);
        $evolutionReports = array_map(static fn (UserEvolutionReportResponse $report): array => $report->toArray(), $evolution);
        $riskReports = array_map(static fn (UserRiskReportResponse $report): array => $report->toArray(), $risks);
        $userIds = collect([...$sessionReports, ...$telemetryReports, ...$evolutionReports, ...$riskReports])
            ->pluck('user_id')->filter()->unique()->values();
        $users = UserModel::query()->whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');
        $withUser = static function (array $report) use ($users): array {
            $user = $users->get($report['user_id']);
            $report['user_name'] = $user?->name ?? 'Usuario no disponible';
            $report['user_email'] = $user?->email;

            return $report;
        };

        return view('simulations.reports', [
            'sessionReports' => array_map($withUser, $sessionReports),
            'telemetryReports' => array_map($withUser, $telemetryReports),
            'evolutionReports' => array_map($withUser, $evolutionReports),
            'riskReports' => array_map($withUser, $riskReports),
        ]);
    }
}
