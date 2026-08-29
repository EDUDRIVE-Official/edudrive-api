<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReportAiIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'ai_system_id' => ['required', 'uuid'],
            'severity' => ['required', 'string'],
            'description' => ['required', 'string'],
        ];
    }
}
