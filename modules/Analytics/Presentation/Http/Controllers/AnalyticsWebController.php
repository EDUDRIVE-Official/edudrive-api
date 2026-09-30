<?php

declare(strict_types=1);

namespace Modules\Analytics\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Analytics\Application\Commands\RequestAnalyticsReportCommand;
use Modules\Analytics\Domain\Enums\AnalyticsReportType;
use Modules\Analytics\Presentation\Http\Requests\RequestAnalyticsReportRequest;
use Modules\AsyncProcessing\Application\Exceptions\AsyncJobNotFound;
use Modules\AsyncProcessing\Application\Queries\GetAsyncJobQuery;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Infrastructure\Persistence\Eloquent\Models\AsyncJobModel;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;

final class AnalyticsWebController
{
    public function index(Request $request): View
    {
        return view('analytics.index', [
            'types' => AnalyticsReportType::cases(),
            'reports' => AsyncJobModel::query()
                ->where('requested_by_user_id', (string) $request->user()?->getAuthIdentifier())
                ->where('type', 'like', 'analytics.%')
                ->latest()
                ->limit(20)
                ->get(['id', 'type', 'status', 'created_at', 'completed_at']),
        ]);
    }

    public function store(RequestAnalyticsReportRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RequestAnalyticsReportCommand(
            type: (string) $data['type'],
            requestedByUserId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof AsyncJobResponse);

        return redirect()->route('analytics.reports.show', $result->id)
            ->with('status', 'Reporte solicitado correctamente.');
    }

    public function show(string $asyncJobId, Request $request, QueryBus $queryBus): View
    {
        try {
            $job = $queryBus->ask(new GetAsyncJobQuery(
                asyncJobId: $asyncJobId,
                requestedByUserId: (string) $request->user()?->getAuthIdentifier(),
            ));
        } catch (AsyncJobNotFound) {
            abort(404);
        }
        assert($job instanceof AsyncJobResponse);

        return view('analytics.show', ['job' => $job->toArray()]);
    }
}
