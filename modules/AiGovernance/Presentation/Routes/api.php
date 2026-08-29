<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AiGovernance\Presentation\Http\Controllers\AiDecisionController;
use Modules\AiGovernance\Presentation\Http\Controllers\AiGatewayController;
use Modules\AiGovernance\Presentation\Http\Controllers\AiIncidentController;
use Modules\AiGovernance\Presentation\Http\Controllers\AiModelController;
use Modules\AiGovernance\Presentation\Http\Controllers\AiPromptController;
use Modules\AiGovernance\Presentation\Http\Controllers\AiProviderEvaluationController;
use Modules\AiGovernance\Presentation\Http\Controllers\AiSystemController;

Route::prefix('api/v1/ai-governance')
    ->name('api.v1.ai-governance.')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::middleware('permission:ai_governance.view')->group(function (): void {
            Route::get('/provider-evaluations', [AiProviderEvaluationController::class, 'index'])->name('provider-evaluations.index');
            Route::get('/provider-evaluations/{providerEvaluationId}', [AiProviderEvaluationController::class, 'show'])->whereUuid('providerEvaluationId')->name('provider-evaluations.show');

            Route::get('/models', [AiModelController::class, 'index'])->name('models.index');
            Route::get('/models/{modelId}', [AiModelController::class, 'show'])->whereUuid('modelId')->name('models.show');

            Route::get('/prompts', [AiPromptController::class, 'index'])->name('prompts.index');
            Route::get('/prompts/{promptId}', [AiPromptController::class, 'show'])->whereUuid('promptId')->name('prompts.show');

            Route::get('/systems', [AiSystemController::class, 'index'])->name('systems.index');
            Route::get('/systems/{aiSystemId}', [AiSystemController::class, 'show'])->whereUuid('aiSystemId')->name('systems.show');
            Route::get('/systems/{aiSystemId}/decisions', [AiDecisionController::class, 'index'])->whereUuid('aiSystemId')->name('systems.decisions.index');
            Route::get('/systems/{aiSystemId}/incidents', [AiIncidentController::class, 'index'])->whereUuid('aiSystemId')->name('systems.incidents.index');

            Route::get('/decisions/{decisionId}', [AiDecisionController::class, 'show'])->whereUuid('decisionId')->name('decisions.show');
            Route::get('/incidents/{incidentId}', [AiIncidentController::class, 'show'])->whereUuid('incidentId')->name('incidents.show');
        });

        Route::middleware('permission:ai_governance.manage')->group(function (): void {
            Route::post('/provider-evaluations', [AiProviderEvaluationController::class, 'store'])->name('provider-evaluations.store');
            Route::post('/provider-evaluations/{providerEvaluationId}/approve', [AiProviderEvaluationController::class, 'approve'])->whereUuid('providerEvaluationId')->name('provider-evaluations.approve');
            Route::post('/provider-evaluations/{providerEvaluationId}/reject', [AiProviderEvaluationController::class, 'reject'])->whereUuid('providerEvaluationId')->name('provider-evaluations.reject');
            Route::post('/provider-evaluations/{providerEvaluationId}/require-reevaluation', [AiProviderEvaluationController::class, 'requireReevaluation'])->whereUuid('providerEvaluationId')->name('provider-evaluations.require-reevaluation');

            Route::post('/models', [AiModelController::class, 'store'])->name('models.store');
            Route::post('/models/{modelId}/approve', [AiModelController::class, 'approve'])->whereUuid('modelId')->name('models.approve');
            Route::post('/models/{modelId}/deprecate', [AiModelController::class, 'deprecate'])->whereUuid('modelId')->name('models.deprecate');
            Route::post('/models/{modelId}/retire', [AiModelController::class, 'retire'])->whereUuid('modelId')->name('models.retire');

            Route::post('/prompts', [AiPromptController::class, 'store'])->name('prompts.store');
            Route::put('/prompts/{promptId}/content', [AiPromptController::class, 'updateContent'])->whereUuid('promptId')->name('prompts.update-content');
            Route::post('/prompts/{promptId}/approve', [AiPromptController::class, 'approve'])->whereUuid('promptId')->name('prompts.approve');
            Route::post('/prompts/{promptId}/retire', [AiPromptController::class, 'retire'])->whereUuid('promptId')->name('prompts.retire');

            Route::post('/systems', [AiSystemController::class, 'store'])->name('systems.store');
            Route::post('/systems/{aiSystemId}/promote', [AiSystemController::class, 'promote'])->whereUuid('aiSystemId')->name('systems.promote');
            Route::post('/systems/{aiSystemId}/grant-extraordinary-approval', [AiSystemController::class, 'grantExtraordinaryApproval'])->whereUuid('aiSystemId')->name('systems.grant-extraordinary-approval');
            Route::post('/systems/{aiSystemId}/approve-by-committee', [AiSystemController::class, 'approveByCommittee'])->whereUuid('aiSystemId')->name('systems.approve-by-committee');

            Route::post('/decisions/{decisionId}/approve', [AiDecisionController::class, 'approve'])->whereUuid('decisionId')->name('decisions.approve');
            Route::post('/decisions/{decisionId}/reject', [AiDecisionController::class, 'reject'])->whereUuid('decisionId')->name('decisions.reject');

            Route::post('/incidents', [AiIncidentController::class, 'store'])->name('incidents.store');
            Route::post('/incidents/{incidentId}/start-investigation', [AiIncidentController::class, 'startInvestigation'])->whereUuid('incidentId')->name('incidents.start-investigation');
            Route::post('/incidents/{incidentId}/resolve', [AiIncidentController::class, 'resolve'])->whereUuid('incidentId')->name('incidents.resolve');

            Route::post('/gateway/invoke', [AiGatewayController::class, 'invoke'])
                ->middleware('throttle:ai-gateway')
                ->name('gateway.invoke');
        });
    });
