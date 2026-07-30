<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academic\Presentation\Http\Controllers\CourseController;

Route::prefix('api/v1/academic')->group(function (): void {
    Route::get('/status', static function (): array {
        return [
            'data' => [
                'module' => 'Academic',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ];
    });

    Route::get(
        '/courses',
        [CourseController::class, 'index'],
    );

    Route::post(
        '/courses',
        [CourseController::class, 'store'],
    );
});
