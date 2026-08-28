<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\GetCourseCompetencyReportQuery;
use Modules\Academic\Application\Responses\CourseCompetencyReportResponse;
use Modules\Academic\Application\Services\CourseExamAttemptsLookup;
use Modules\Academic\Application\Services\ReportCourseResolver;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

final readonly class GetCourseCompetencyReportHandler
{
    public function __construct(
        private ReportCourseResolver $courseResolver,
        private CourseExamAttemptsLookup $attemptsLookup,
        private CompetencyRepository $competencies,
    ) {}

    /** @return list<CourseCompetencyReportResponse> */
    public function handle(GetCourseCompetencyReportQuery $query): array
    {
        return array_map(
            fn (Course $course): CourseCompetencyReportResponse => $this->reportFor($course),
            $this->courseResolver->resolve($query->courseIds),
        );
    }

    private function reportFor(Course $course): CourseCompetencyReportResponse
    {
        $attempts = $this->attemptsLookup->submittedAttemptsFor($course->id());

        /** @var array<string, list<int>> $percentagesByCompetencyId */
        $percentagesByCompetencyId = [];

        foreach ($attempts as $attempt) {
            foreach ($attempt->competencyBreakdown() as $grade) {
                $competencyId = $grade->competencyId()->value();
                $percentagesByCompetencyId[$competencyId][] = $grade->percentage();
            }
        }

        $competencies = [];
        foreach ($percentagesByCompetencyId as $competencyId => $percentages) {
            $competency = $this->competencies->findById(
                CompetencyId::fromString($competencyId),
            );

            $competencies[] = [
                'competency_id' => $competencyId,
                'competency_code' => $competency?->code()->value() ?? $competencyId,
                'average_percentage' => round(array_sum($percentages) / count($percentages), 2),
                'sample_count' => count($percentages),
            ];
        }

        return new CourseCompetencyReportResponse(
            courseId: $course->id()->value(),
            courseCode: $course->code()->value(),
            courseTitle: $course->title()->value(),
            competencies: $competencies,
        );
    }
}
