<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;

uses(RefreshDatabase::class);

it('enumera los ids de leccion de todas las unidades del curso', function (): void {
    $course = createDraftCourseForPublishing('PRG-CAT-01');
    $catalog = new CourseLessonCatalog(app(UnitContentRepository::class));

    expect($catalog->lessonIdsFor($course))->toHaveCount(1);
});

it('ignora unidades sin contenido publicado', function (): void {
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('PRG-CAT-02'),
        title: CourseTitle::fromString('Curso sin contenido'),
    );
    addMinimalCurriculum($course);
    app(CourseRepository::class)->save($course);

    $catalog = new CourseLessonCatalog(app(UnitContentRepository::class));

    expect($catalog->lessonIdsFor($course))->toBe([]);
});
