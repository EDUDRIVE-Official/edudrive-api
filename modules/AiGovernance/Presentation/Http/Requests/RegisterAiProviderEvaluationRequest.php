<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterAiProviderEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'provider_name' => ['required', 'string', 'max:150'],
            'data_location' => ['required', 'string', 'max:150'],
            'retention_policy' => ['required', 'string'],
            'security_review_notes' => ['nullable', 'string'],
        ];
    }
}
