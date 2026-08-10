<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academic\Presentation\Http\Controllers\AcademicStatusController;
use Modules\Academic\Presentation\Http\Controllers\CompetencyController;
use Modules\Academic\Presentation\Http\Controllers\CourseController;
use Modules\Academic\Presentation\Http\Controllers\ProgramController;

Route::prefix('api/v1/academic')
    ->name('api.v1.academic.')
    ->group(function (): void {
        Route::get('/status', AcademicStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::middleware('permission:competencies.view')->group(function (): void {
                Route::get('/competencies', [CompetencyController::class, 'index'])
                    ->name('competencies.index');
            });

            Route::middleware('permission:competencies.manage')->group(function (): void {
                Route::post('/competencies', [CompetencyController::class, 'store'])
                    ->name('competencies.store');
                Route::post('/competencies/{competencyId}/subcompetencies', [CompetencyController::class, 'addSubcompetency'])
                    ->whereUuid('competencyId')
                    ->name('competencies.subcompetencies.store');
                Route::post('/competencies/{competencyId}/subcompetencies/{subcompetencyCode}/indicators', [CompetencyController::class, 'addIndicator'])
                    ->whereUuid('competencyId')
                    ->name('competencies.indicators.store');
            });

            Route::middleware('permission:courses.view')->group(function (): void {
                Route::get('/courses', [CourseController::class, 'index'])
                    ->name('courses.index');
                Route::get('/courses/{courseId}/curriculum', [CourseController::class, 'curriculum'])
                    ->whereUuid('courseId')
                    ->name('courses.curriculum.show');
                Route::get('/courses/{courseId}/versions', [CourseController::class, 'versions'])
                    ->whereUuid('courseId')
                    ->name('courses.versions.index');
                Route::get('/courses/{courseId}/versions/{versionNumber}', [CourseController::class, 'version'])
                    ->whereUuid('courseId')
                    ->whereNumber('versionNumber')
                    ->name('courses.versions.show');
                Route::get('/courses/{courseId}/units/{unitId}/content', [CourseController::class, 'unitContent'])
                    ->whereUuid('courseId')
                    ->whereUuid('unitId')
                    ->name('courses.units.content.show');
            });

            Route::middleware('permission:courses.manage')->group(function (): void {
                Route::post('/courses', [CourseController::class, 'store'])
                    ->name('courses.store');

                Route::post('/courses/{courseId}/publish', [CourseController::class, 'publish'])
                    ->whereUuid('courseId')
                    ->name('courses.publish');

                Route::post('/courses/{courseId}/submit-for-review', [CourseController::class, 'submitForReview'])
                    ->whereUuid('courseId')
                    ->name('courses.submit-for-review');

                Route::post('/courses/{courseId}/approve', [CourseController::class, 'approve'])
                    ->whereUuid('courseId')
                    ->name('courses.approve');

                Route::post('/courses/{courseId}/send-back-to-draft', [CourseController::class, 'sendBackToDraft'])
                    ->whereUuid('courseId')
                    ->name('courses.send-back-to-draft');

                Route::post('/courses/{courseId}/reopen', [CourseController::class, 'reopen'])
                    ->whereUuid('courseId')
                    ->name('courses.reopen');

                Route::post('/courses/{courseId}/archive', [CourseController::class, 'archive'])
                    ->whereUuid('courseId')
                    ->name('courses.archive');

                Route::put('/courses/{courseId}/curriculum', [CourseController::class, 'replaceCurriculum'])
                    ->whereUuid('courseId')
                    ->name('courses.curriculum.update');
                Route::put('/courses/{courseId}/units/{unitId}/content', [CourseController::class, 'replaceUnitContent'])
                    ->whereUuid('courseId')
                    ->whereUuid('unitId')
                    ->name('courses.units.content.update');
            });

            Route::middleware('permission:programs.view')->group(function (): void {
                Route::get('/programs', [ProgramController::class, 'index'])
                    ->name('programs.index');
            });

            Route::middleware('permission:programs.manage')->group(function (): void {
                Route::post('/programs', [ProgramController::class, 'store'])
                    ->name('programs.store');
                Route::patch('/programs/{programId}/audience', [ProgramController::class, 'changeAudience'])
                    ->whereUuid('programId')
                    ->name('programs.audience.update');
                Route::put('/programs/{programId}/courses', [ProgramController::class, 'replaceCourses'])
                    ->whereUuid('programId')
                    ->name('programs.courses.update');
                Route::post('/programs/{programId}/publish', [ProgramController::class, 'publish'])
                    ->whereUuid('programId')
                    ->name('programs.publish');
                Route::post('/programs/{programId}/archive', [ProgramController::class, 'archive'])
                    ->whereUuid('programId')
                    ->name('programs.archive');
            });
        });
    });
