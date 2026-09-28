<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academic\Presentation\Http\Controllers\CourseWebController;
use Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController;
use Modules\Academic\Presentation\Http\Controllers\EditorialPreviewController;
use Modules\Academic\Presentation\Http\Controllers\PilotInstrumentController;
use Modules\Academic\Presentation\Http\Controllers\PilotJourneyController;

Route::middleware(['web', 'auth', 'pilot.reviewer'])->prefix('pilot-instruments')->name('pilot-instruments.')->group(function (): void {
    Route::get('/visual', [PilotInstrumentController::class, 'visual'])->name('visual');
    Route::get('/van', [PilotInstrumentController::class, 'van'])->name('van');
    Route::get('/descent', [PilotInstrumentController::class, 'descent'])->name('descent');
    Route::get('/barrier', [PilotInstrumentController::class, 'barrier'])->name('barrier');
    Route::get('/visual-sequence', [PilotInstrumentController::class, 'visualSequence'])->name('visual-sequence');
    Route::get('/visual-review', [PilotInstrumentController::class, 'visualReview'])->name('visual-review');
    Route::get('/visual-review-guide', [PilotInstrumentController::class, 'visualReviewGuide'])->name('visual-review-guide');
    Route::post('/visual-review', [PilotInstrumentController::class, 'storeVisualReview'])->middleware('throttle:30,1')->name('visual-review.store');
    Route::get('/visual-review-summary', [PilotInstrumentController::class, 'visualReviewSummary'])->name('visual-review-summary');
});

Route::middleware(['web', 'auth', 'permission:courses.manage'])->prefix('pilot-instruments')->name('pilot-instruments.')->group(function (): void {
    Route::get('/editorial-preview', EditorialPreviewController::class)->name('editorial-preview');
    Route::get('/', [PilotInstrumentController::class, 'index'])->name('index');
    Route::get('/unit', [PilotInstrumentController::class, 'unit'])->name('unit');
    Route::get('/visual-reviewers', [PilotInstrumentController::class, 'visualReviewers'])->middleware('permission:roles.manage')->name('visual-reviewers');
    Route::post('/visual-reviewers', [PilotInstrumentController::class, 'storeVisualReviewer'])->middleware(['permission:roles.manage', 'throttle:20,1'])->name('visual-reviewers.store');
    Route::get('/visual-readiness', [PilotInstrumentController::class, 'visualReadiness'])->name('visual-readiness');
    Route::get('/pilot-protocol', [PilotInstrumentController::class, 'pilotProtocol'])->name('pilot-protocol');
    Route::get('/pilot-forms', [PilotInstrumentController::class, 'pilotForms'])->name('pilot-forms');
    Route::get('/pilot-dossier', [PilotInstrumentController::class, 'pilotDossier'])->name('pilot-dossier');
    Route::get('/normative-alignment', [PilotInstrumentController::class, 'normativeAlignment'])->name('normative-alignment');
    Route::get('/institutional-review-package', [PilotInstrumentController::class, 'institutionalReviewPackage'])->name('institutional-review-package');
    Route::get('/institutional-submission', [PilotInstrumentController::class, 'institutionalSubmission'])->name('institutional-submission');
    Route::get('/reviewer-onboarding', [PilotInstrumentController::class, 'reviewerOnboarding'])->name('reviewer-onboarding');
    Route::get('/review-coordination', [PilotInstrumentController::class, 'reviewCoordination'])->name('review-coordination');
    Route::get('/visual-candidates', [PilotInstrumentController::class, 'visualCandidates'])->name('visual-candidates');
    Route::post('/visual-candidates', [PilotInstrumentController::class, 'storeVisualCandidate'])->middleware('throttle:20,1')->name('visual-candidates.store');
    Route::post('/visual-candidates/create-draft', [PilotInstrumentController::class, 'createPilotDraft'])->middleware('throttle:5,1')->name('visual-candidates.create-draft');
    Route::get('/journey', [PilotJourneyController::class, 'show'])->name('journey');
    Route::post('/journey/start', [PilotJourneyController::class, 'start'])->block()->name('journey.start');
    Route::post('/journey', [PilotJourneyController::class, 'update'])->block()->name('journey.update');
    Route::post('/', [PilotInstrumentController::class, 'start'])->middleware('throttle:20,1')->name('start');
    Route::get('/{runId}', [PilotInstrumentController::class, 'show'])->whereUuid('runId')->name('show');
    Route::post('/{runId}', [PilotInstrumentController::class, 'update'])->whereUuid('runId')->middleware('throttle:60,1')->name('update');
});

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/descubro/cruzar-acompanado', [DescubroCrossingController::class, 'show'])->name('descubro.crossing.show');
    Route::post('/descubro/cruzar-acompanado', [DescubroCrossingController::class, 'update'])->middleware('throttle:60,1')->block()->name('descubro.crossing.update');
    Route::middleware('permission:courses.view')->group(function (): void {
        Route::get('/courses', [CourseWebController::class, 'index'])
            ->name('courses.index');

        Route::get('/courses/{courseId}', [CourseWebController::class, 'show'])
            ->whereUuid('courseId')
            ->name('courses.show');

        Route::post('/courses/{courseId}/enroll', [CourseWebController::class, 'enroll'])
            ->whereUuid('courseId')
            ->name('courses.enroll');

        Route::get('/my-courses/{enrollmentId}', [CourseWebController::class, 'learn'])
            ->whereUuid('enrollmentId')
            ->name('courses.learn');

        Route::post('/my-courses/{enrollmentId}/lessons/{lessonId}/complete', [CourseWebController::class, 'completeLesson'])
            ->whereUuid(['enrollmentId', 'lessonId'])
            ->name('courses.lessons.complete');

        Route::post('/my-courses/{enrollmentId}/transfer-check', [CourseWebController::class, 'storeTransferCheck'])
            ->whereUuid('enrollmentId')
            ->name('courses.transfer-check.store');

        Route::post('/my-courses/{enrollmentId}/entry-diagnostic', [CourseWebController::class, 'storeEntryDiagnostic'])
            ->whereUuid('enrollmentId')
            ->name('courses.entry-diagnostic.store');
    });

    Route::middleware('permission:courses.manage')->group(function (): void {
        Route::get('/courses/{courseId}/preview', [CourseWebController::class, 'preview'])
            ->whereUuid('courseId')
            ->name('courses.preview');

        Route::get('/courses/create', [CourseWebController::class, 'create'])
            ->name('courses.create');

        Route::post('/courses', [CourseWebController::class, 'store'])
            ->name('courses.store');

        Route::post('/courses/{courseId}/submit-for-review', [CourseWebController::class, 'submitForReview'])
            ->whereUuid('courseId')
            ->name('courses.submitForReview');

        Route::post('/courses/{courseId}/approve', [CourseWebController::class, 'approve'])
            ->whereUuid('courseId')
            ->name('courses.approve');

        Route::post('/courses/{courseId}/publish', [CourseWebController::class, 'publish'])
            ->whereUuid('courseId')
            ->name('courses.publish');

        Route::post('/courses/{courseId}/archive', [CourseWebController::class, 'archive'])
            ->whereUuid('courseId')
            ->name('courses.archive');
    });
});
