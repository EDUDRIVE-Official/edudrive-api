<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FileStorage\Presentation\Http\Controllers\FileWebController;

Route::middleware(['web', 'auth'])->prefix('mis-archivos')->name('files.')->group(function (): void {
    Route::get('/', [FileWebController::class, 'index'])->name('index');
    Route::post('/', [FileWebController::class, 'store'])->name('store');
    Route::get('/{fileId}/descargar', [FileWebController::class, 'download'])->whereUuid('fileId')->name('download');
    Route::delete('/{fileId}', [FileWebController::class, 'destroy'])->whereUuid('fileId')->name('destroy');
});
