<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Exceptions\CourseAlreadyPublished;
use Modules\Academic\Domain\Exceptions\CourseCannotBeReopened;
use Modules\Academic\Domain\Exceptions\CourseReviewStateInvalid;
use Modules\Academic\Domain\ValueObjects\CourseTitle;

it('envia un curso de borrador a revision', function (): void {
    $course = createAcademicCourse();

    $course->submitForReview();

    expect($course->status())->toBe(CourseStatus::UnderReview);
});

it('impide enviar a revision un curso que no es borrador', function (Course $course): void {

    $course->submitForReview();
})->throws(
    CourseReviewStateInvalid::class,
    'El curso no se encuentra en el estado requerido para esta accion.',
)->with([
    'en revision' => fn (): Course => tap(createAcademicCourse(), fn (Course $course) => $course->submitForReview()),
    'aprobado' => fn (): Course => tap(createAcademicCourse(), function (Course $course): void {
        $course->submitForReview();
        $course->approve();
    }),
    'publicado' => fn (): Course => tap(createAcademicCourse(), function (Course $course): void {
        $course->replaceCurriculum(validAggregateCurriculum());
        $course->submitForReview();
        $course->approve();
        $course->publish(new DateTimeImmutable('2026-08-10T08:00:00+00:00'), validAggregateCoverage());
    }),
    'archivado' => fn (): Course => tap(createAcademicCourse(), fn (Course $course) => $course->archive(new DateTimeImmutable('2026-08-10T08:00:00+00:00'))),
]);

it('aprueba un curso en revision', function (): void {
    $course = createAcademicCourse();
    $course->submitForReview();

    $course->approve();

    expect($course->status())->toBe(CourseStatus::Approved);
});

it('impide aprobar un curso que no esta en revision', function (Course $course): void {

    $course->approve();
})->throws(
    CourseReviewStateInvalid::class,
    'El curso no se encuentra en el estado requerido para esta accion.',
)->with([
    'borrador' => fn (): Course => createAcademicCourse(),
    'aprobado' => fn (): Course => tap(createAcademicCourse(), function (Course $course): void {
        $course->submitForReview();
        $course->approve();
    }),
    'publicado' => fn (): Course => tap(createAcademicCourse(), function (Course $course): void {
        $course->replaceCurriculum(validAggregateCurriculum());
        $course->submitForReview();
        $course->approve();
        $course->publish(new DateTimeImmutable('2026-08-10T08:00:00+00:00'), validAggregateCoverage());
    }),
    'archivado' => fn (): Course => tap(createAcademicCourse(), fn (Course $course) => $course->archive(new DateTimeImmutable('2026-08-10T08:00:00+00:00'))),
]);

it('devuelve a borrador un curso en revision o aprobado', function (Course $course): void {

    $course->sendBackToDraft();

    expect($course->status())->toBe(CourseStatus::Draft);
})->with([
    'en revision' => fn (): Course => tap(createAcademicCourse(), fn (Course $course) => $course->submitForReview()),
    'aprobado' => fn (): Course => tap(createAcademicCourse(), function (Course $course): void {
        $course->submitForReview();
        $course->approve();
    }),
]);

it('impide devolver a borrador un curso que no esta en revision ni aprobado', function (Course $course): void {

    $course->sendBackToDraft();
})->throws(
    CourseReviewStateInvalid::class,
    'El curso no se encuentra en el estado requerido para esta accion.',
)->with([
    'borrador' => fn (): Course => createAcademicCourse(),
    'publicado' => fn (): Course => tap(createAcademicCourse(), function (Course $course): void {
        $course->replaceCurriculum(validAggregateCurriculum());
        $course->submitForReview();
        $course->approve();
        $course->publish(new DateTimeImmutable('2026-08-10T08:00:00+00:00'), validAggregateCoverage());
    }),
    'archivado' => fn (): Course => tap(createAcademicCourse(), fn (Course $course) => $course->archive(new DateTimeImmutable('2026-08-10T08:00:00+00:00'))),
]);

it('reabre un curso publicado a borrador', function (): void {
    $course = createAcademicCourse();
    $course->replaceCurriculum(validAggregateCurriculum());
    $course->submitForReview();
    $course->approve();
    $course->publish(new DateTimeImmutable('2026-08-10T08:00:00+00:00'), validAggregateCoverage());

    $course->reopen();

    expect($course->status())->toBe(CourseStatus::Draft)
        ->and($course->publishedAt())->toBeNull()
        ->and($course->modules())->toHaveCount(2);
});

it('impide reabrir un curso que no esta publicado', function (Course $course): void {

    $course->reopen();
})->throws(
    CourseCannotBeReopened::class,
    'Solo un curso publicado puede reabrirse.',
)->with([
    'borrador' => fn (): Course => createAcademicCourse(),
    'en revision' => fn (): Course => tap(createAcademicCourse(), fn (Course $course) => $course->submitForReview()),
    'aprobado' => fn (): Course => tap(createAcademicCourse(), function (Course $course): void {
        $course->submitForReview();
        $course->approve();
    }),
    'archivado' => fn (): Course => tap(createAcademicCourse(), fn (Course $course) => $course->archive(new DateTimeImmutable('2026-08-10T08:00:00+00:00'))),
]);

it('permite reabrir, modificar y volver a publicar un curso', function (): void {
    $course = createAcademicCourse();
    $course->replaceCurriculum(validAggregateCurriculum());
    $course->submitForReview();
    $course->approve();
    $course->publish(new DateTimeImmutable('2026-08-10T08:00:00+00:00'), validAggregateCoverage());

    $course->reopen();

    $course->rename(CourseTitle::fromString('Segunda version'));
    $course->submitForReview();
    $course->approve();
    $course->publish(new DateTimeImmutable('2026-08-11T08:00:00+00:00'), validAggregateCoverage());

    expect($course->status())->toBe(CourseStatus::Published)
        ->and($course->title()->value())->toBe('Segunda version');
});

it('impide publicar un curso que no esta aprobado', function (Course $course): void {
    $course->publish(new DateTimeImmutable('2026-08-10T08:00:00+00:00'), validAggregateCoverage());
})->throws(
    CourseReviewStateInvalid::class,
    'El curso no se encuentra en el estado requerido para esta accion.',
)->with([
    'borrador' => fn (): Course => createAcademicCourse(),
    'en revision' => fn (): Course => tap(createAcademicCourse(), fn (Course $course) => $course->submitForReview()),
]);

it('sigue impidiendo publicar dos veces un curso', function (): void {
    $course = createAcademicCourse();
    $course->replaceCurriculum(validAggregateCurriculum());
    $course->submitForReview();
    $course->approve();

    $course->publish(new DateTimeImmutable('2026-08-10T08:00:00+00:00'), validAggregateCoverage());
    $course->publish(new DateTimeImmutable('2026-08-10T09:00:00+00:00'), validAggregateCoverage());
})->throws(
    CourseAlreadyPublished::class,
    'El curso ya está publicado.',
);
