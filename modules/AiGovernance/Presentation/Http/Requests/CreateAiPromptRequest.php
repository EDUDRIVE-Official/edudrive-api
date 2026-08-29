<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAiPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:150'],
            'purpose' => ['required', 'string'],
            'model_id' => ['nullable', 'uuid'],
            'author_id' => ['nullable', 'string', 'max:100'],
            'content' => ['required', 'string'],
        ];
    }
}
