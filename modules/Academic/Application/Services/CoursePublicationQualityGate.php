<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Services;

use DateTimeImmutable;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Exceptions\CoursePedagogicalQualityRequired;
use Modules\Academic\Domain\Enums\ContentBlockType;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class CoursePublicationQualityGate
{
    public function __construct(private CourseRepository $courses, private UnitContentRepository $contents) {}

    public function assertReady(CourseId $courseId): void
    {
        $course = $this->courses->findById($courseId);
        if ($course === null) throw CourseNotFound::withId($courseId->value());
        if (! str_starts_with($course->code()->value(), 'EDU-EXP-')) return;

        $issues = [];
        foreach ($course->modules() as $module) {
            foreach ($module->units() as $unit) {
                foreach ($this->contents->findForCourseUnit($courseId, $unit->id())?->lessons() ?? [] as $lesson) {
                    $design = $lesson->learningDesign();
                    if ($design === null) $issues[] = $lesson->title().': sin trazabilidad';
                    if ($design?->stage === 'pending_review') $issues[] = $lesson->title().': público pendiente de revisión';
                    if ($design !== null && $design->normativeSources === []) $issues[] = $lesson->title().': sin fuente';
                    if ($design !== null && $design->normativeSources !== [] && ! $design->sourcesAreCurrent(new DateTimeImmutable('today'))) $issues[] = $lesson->title().': fuente pendiente de revisión anual';
                    if ($lesson->durationMinutes() === null) $issues[] = $lesson->title().': sin duración';
                    if (count($lesson->blocks()) < 2) $issues[] = $lesson->title().': experiencia demasiado breve';
                    if (! collect($lesson->blocks())->contains(fn ($block): bool => $block->type() === ContentBlockType::Scenario)) $issues[] = $lesson->title().': sin decisión práctica';
                }
            }
        }

        if ($issues !== []) throw CoursePedagogicalQualityRequired::withIssues($issues);
    }
}
