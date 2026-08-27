<?php

declare(strict_types=1);

namespace Modules\Notification\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCommunicationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject_template' => ['required', 'string'],
            'body_template' => ['required', 'string'],
            'variables' => ['present', 'array'],
            'variables.*' => ['string', 'max:100'],
        ];
    }
}
