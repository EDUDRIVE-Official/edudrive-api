<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academic\Presentation\Http\Controllers\AcademicReportController;
use Modules\Academic\Presentation\Http\Controllers\AcademicStatusController;
use Modules\Academic\Presentation\Http\Controllers\CompetencyController;
use Modules\Academic\Presentation\Http\Controllers\CourseController;
use Modules\Academic\Presentation\Http\Controllers\EnrollmentController;
use Modules\Academic\Presentation\Http\Controllers\EnrollmentProgressController;
use Modules\Academic\Presentation\Http\Controllers\ExamAttemptController;
use Modules\Academic\Presentation\Http\Controllers\ExamController;
use Modules\Academic\Presentation\Http\Controllers\OrganizationReportController;
use Modules\Academic\Presentation\Http\Controllers\ProgramController;
use Modules\Academic\Presentation\Http\Controllers\QuestionController;
use Modules\Academic\Presentation\Http\Controllers\TheoryExamController;

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

                Route::post('/courses/import', [CourseController::class, 'bulkImport'])
                    ->name('courses.import');
            });

            Route::middleware('permission:exports.view')->group(function (): void {
                Route::post('/courses/export', [CourseController::class, 'export'])
                    ->name('courses.export');
            });

            Route::middleware('permission:courses.manage')->group(function (): void {
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

            Route::middleware('permission:questions.view')->group(function (): void {
                Route::get('/questions', [QuestionController::class, 'index'])
                    ->name('questions.index');
                Route::get('/questions/{questionId}', [QuestionController::class, 'show'])
                    ->whereUuid('questionId')
                    ->name('questions.show');
            });

            Route::middleware('permission:questions.manage')->group(function (): void {
                Route::post('/questions', [QuestionController::class, 'store'])
                    ->name('questions.store');
                Route::post('/questions/import', [QuestionController::class, 'bulkImport'])
                    ->name('questions.import');
                Route::put('/questions/{questionId}', [QuestionController::class, 'update'])
                    ->whereUuid('questionId')
                    ->name('questions.update');
                Route::delete('/questions/{questionId}', [QuestionController::class, 'destroy'])
                    ->whereUuid('questionId')
                    ->name('questions.destroy');
            });

            Route::middleware('permission:exams.view')->group(function (): void {
                Route::get('/exams', [ExamController::class, 'index'])
                    ->name('exams.index');
                Route::get('/exams/{examId}', [ExamController::class, 'show'])
                    ->whereUuid('examId')
                    ->name('exams.show');

                Route::get('/theory-exams', [TheoryExamController::class, 'index'])
                    ->name('theory-exams.index');
                Route::get('/theory-exams/{examId}', [TheoryExamController::class, 'show'])
                    ->whereUuid('examId')
                    ->name('theory-exams.show');
            });

            Route::middleware('permission:exams.manage')->group(function (): void {
                Route::post('/exams', [ExamController::class, 'store'])
                    ->name('exams.store');
                Route::put('/exams/{examId}', [ExamController::class, 'update'])
                    ->whereUuid('examId')
                    ->name('exams.update');
                Route::delete('/exams/{examId}', [ExamController::class, 'destroy'])
                    ->whereUuid('examId')
                    ->name('exams.destroy');
            });

            Route::middleware('permission:exam_attempts.view')->group(function (): void {
                Route::get('/exam-attempts', [ExamAttemptController::class, 'index'])
                    ->name('exam-attempts.index');
            });

            Route::get('/theory-attempts', [TheoryExamController::class, 'attempts'])
                ->name('theory-attempts.index');

            Route::get('/exam-attempts/{attemptId}', [ExamAttemptController::class, 'show'])
                ->whereUuid('attemptId')
                ->name('exam-attempts.show');

            Route::post('/exam-attempts', [ExamAttemptController::class, 'start'])
                ->name('exam-attempts.store');

            Route::post('/theory-exams/{examId}/start', [TheoryExamController::class, 'start'])
                ->whereUuid('examId')
                ->name('theory-exams.start');

            Route::put('/exam-attempts/{attemptId}/questions/{position}', [ExamAttemptController::class, 'answer'])
                ->whereUuid('attemptId')
                ->whereNumber('position')
                ->name('exam-attempts.questions.update');

            Route::post('/exam-attempts/{attemptId}/submit', [ExamAttemptController::class, 'submit'])
                ->whereUuid('attemptId')
                ->name('exam-attempts.submit');

            Route::post('/exam-attempts/{attemptId}/cancel', [ExamAttemptController::class, 'cancel'])
                ->whereUuid('attemptId')
                ->name('exam-attempts.cancel');

            Route::middleware('permission:enrollments.view')->group(function (): void {
                Route::get('/enrollments', [EnrollmentController::class, 'index'])
                    ->name('enrollments.index');
                Route::get('/enrollments/{enrollmentId}', [EnrollmentController::class, 'show'])
                    ->whereUuid('enrollmentId')
                    ->name('enrollments.show');
            });

            Route::middleware('permission:enrollments.manage')->group(function (): void {
                Route::post('/enrollments', [EnrollmentController::class, 'store'])
                    ->name('enrollments.store');
                Route::post('/enrollments/bulk', [EnrollmentController::class, 'bulk'])
                    ->name('enrollments.bulk');
                Route::post('/enrollments/institutional', [EnrollmentController::class, 'institutional'])
                    ->name('enrollments.institutional');

                Route::post('/enrollments/{enrollmentId}/activate', [EnrollmentController::class, 'activate'])
                    ->whereUuid('enrollmentId')
                    ->name('enrollments.activate');
                Route::post('/enrollments/{enrollmentId}/complete', [EnrollmentController::class, 'complete'])
                    ->whereUuid('enrollmentId')
                    ->name('enrollments.complete');
                Route::post('/enrollments/{enrollmentId}/cancel', [EnrollmentController::class, 'cancel'])
                    ->whereUuid('enrollmentId')
                    ->name('enrollments.cancel');
            });

            Route::middleware('permission:exports.view')->group(function (): void {
                Route::post('/enrollments/export', [EnrollmentController::class, 'export'])
                    ->name('enrollments.export');
            });

            Route::middleware('permission:reports.view')->group(function (): void {
                Route::get('/reports/progress', [AcademicReportController::class, 'progress'])
                    ->name('reports.progress');
                Route::get('/reports/performance', [AcademicReportController::class, 'performance'])
                    ->name('reports.performance');
                Route::get('/reports/approval', [AcademicReportController::class, 'approval'])
                    ->name('reports.approval');
                Route::get('/reports/competencies', [AcademicReportController::class, 'competencies'])
                    ->name('reports.competencies');
                Route::get('/reports/activity', [AcademicReportController::class, 'activity'])
                    ->name('reports.activity');

                Route::get('/reports/organizations/participation', [OrganizationReportController::class, 'participation'])
                    ->name('reports.organizations.participation');
                Route::get('/reports/organizations/completion', [OrganizationReportController::class, 'completion'])
                    ->name('reports.organizations.completion');
                Route::get('/reports/organizations/performance', [OrganizationReportController::class, 'performance'])
                    ->name('reports.organizations.performance');
                Route::get('/reports/organizations/adoption', [OrganizationReportController::class, 'adoption'])
                    ->name('reports.organizations.adoption');
            });

            Route::post('/enrollments/{enrollmentId}/lessons/{lessonId}/complete', [EnrollmentProgressController::class, 'complete'])
                ->whereUuid('enrollmentId')
                ->whereUuid('lessonId')
                ->name('enrollments.lessons.complete');

            Route::get('/enrollments/{enrollmentId}/progress', [EnrollmentProgressController::class, 'show'])
                ->whereUuid('enrollmentId')
                ->name('enrollments.progress.show');

            Route::get('/enrollments/{enrollmentId}/curriculum', [EnrollmentProgressController::class, 'curriculum'])
                ->whereUuid('enrollmentId')
                ->name('enrollments.curriculum.show');

            Route::get('/enrollments/{enrollmentId}/recommendations', [EnrollmentProgressController::class, 'recommendations'])
                ->whereUuid('enrollmentId')
                ->name('enrollments.recommendations.show');
        });
    });
