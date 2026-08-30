<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Application\Queries\GetOrganizationAdoptionReportQuery;
use Modules\Academic\Application\Queries\GetOrganizationCompletionReportQuery;
use Modules\Academic\Application\Queries\GetOrganizationParticipationReportQuery;
use Modules\Academic\Application\Queries\GetOrganizationPerformanceReportQuery;
use Modules\Academic\Application\Responses\OrganizationAdoptionReportResponse;
use Modules\Academic\Application\Responses\OrganizationCompletionReportResponse;
use Modules\Academic\Application\Responses\OrganizationParticipationReportResponse;
use Modules\Academic\Application\Responses\OrganizationPerformanceReportResponse;
use Modules\Authorization\Application\Exceptions\OrganizationNotAccessible;
use Modules\Authorization\Application\Services\AccessibleOrganizationsResolver;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\QueryBus;

final class OrganizationReportController
{
    public function __construct(
        private readonly AccessibleOrganizationsResolver $accessibleOrganizations,
    ) {}

    public function participation(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetOrganizationParticipationReportQuery(organizationIds: $this->organizationIds($request)));
        assert(is_array($result));

        /** @var list<OrganizationParticipationReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (OrganizationParticipationReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    public function completion(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetOrganizationCompletionReportQuery(organizationIds: $this->organizationIds($request)));
        assert(is_array($result));

        /** @var list<OrganizationCompletionReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (OrganizationCompletionReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    public function performance(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetOrganizationPerformanceReportQuery(organizationIds: $this->organizationIds($request)));
        assert(is_array($result));

        /** @var list<OrganizationPerformanceReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (OrganizationPerformanceReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    public function adoption(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetOrganizationAdoptionReportQuery(organizationIds: $this->organizationIds($request)));
        assert(is_array($result));

        /** @var list<OrganizationAdoptionReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (OrganizationAdoptionReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    /** @return list<string> */
    private function organizationIds(Request $request): array
    {
        $requested = self::requestedOrganizationIds($request);

        $accessible = $this->accessibleOrganizations->resolveForPermission(
            (string) $request->user()?->getAuthIdentifier(),
            Permission::ViewReports,
        );

        if ($accessible === null) {
            return $requested;
        }

        if ($requested === []) {
            return $accessible;
        }

        if (array_diff($requested, $accessible) !== []) {
            throw new OrganizationNotAccessible;
        }

        return $requested;
    }

    /** @return list<string> */
    private static function requestedOrganizationIds(Request $request): array
    {
        $organizationIds = $request->query('organization_ids', []);

        if (! is_array($organizationIds)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $organizationId): string => (string) $organizationId, $organizationIds));
    }
}
