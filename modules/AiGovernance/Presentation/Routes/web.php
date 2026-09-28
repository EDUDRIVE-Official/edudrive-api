<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AiGovernance\Presentation\Http\Controllers\AiGovernanceWebController;

Route::middleware(['web', 'auth', 'permission:ai_governance.view'])->group(function (): void {
    Route::get('/admin/gobierno-ia', AiGovernanceWebController::class)->name('ai-governance.dashboard');
});
