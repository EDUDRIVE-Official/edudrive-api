<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Application\Queries\GetCourseActivityReportQuery;
use Modules\Academic\Application\Queries\GetCourseApprovalReportQuery;
use Modules\Academic\Application\Queries\GetCourseCompetencyReportQuery;
use Modules\Academic\Application\Queries\GetCoursePerformanceReportQuery;
use Modules\Academic\Application\Queries\GetCourseProgressReportQuery;
use Modules\Academic\Application\Responses\CourseActivityReportResponse;
use Modules\Academic\Application\Responses\CourseApprovalReportResponse;
use Modules\Academic\Application\Responses\CourseCompetencyReportResponse;
use Modules\Academic\Application\Responses\CoursePerformanceReportResponse;
use Modules\Academic\Application\Responses\CourseProgressReportResponse;
use Modules\Foundation\Application\Bus\QueryBus;

final class AcademicReportController
{
    public function progress(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetCourseProgressReportQuery(courseIds: self::courseIds($request)));
        assert(is_array($result));

        /** @var list<CourseProgressReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (CourseProgressReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    public function performance(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetCoursePerformanceReportQuery(courseIds: self::courseIds($request)));
        assert(is_array($result));

        /** @var list<CoursePerformanceReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (CoursePerformanceReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    public function approval(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetCourseApprovalReportQuery(courseIds: self::courseIds($request)));
        assert(is_array($result));

        /** @var list<CourseApprovalReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (CourseApprovalReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    public function competencies(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetCourseCompetencyReportQuery(courseIds: self::courseIds($request)));
        assert(is_array($result));

        /** @var list<CourseCompetencyReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (CourseCompetencyReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    public function activity(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetCourseActivityReportQuery(courseIds: self::courseIds($request)));
        assert(is_array($result));

        /** @var list<CourseActivityReportResponse> $result */
        return response()->json(['data' => array_map(
            static fn (CourseActivityReportResponse $report): array => $report->toArray(),
            $result,
        )]);
    }

    /** @return list<string> */
    private static function courseIds(Request $request): array
    {
        $courseIds = $request->query('course_ids', []);

        if (! is_array($courseIds)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $courseId): string => (string) $courseId, $courseIds));
    }
}
