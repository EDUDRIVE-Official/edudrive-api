<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterAiModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'provider' => ['required', 'string', 'max:100'],
            'version' => ['required', 'string', 'max:50'],
            'owner_id' => ['nullable', 'string', 'max:100'],
            'use_case' => ['nullable', 'string'],
            'known_risks' => ['nullable', 'string'],
        ];
    }
}
