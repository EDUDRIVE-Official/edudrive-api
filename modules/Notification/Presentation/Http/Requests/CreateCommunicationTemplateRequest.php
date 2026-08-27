<?php

declare(strict_types=1);

namespace Modules\Notification\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCommunicationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'locale' => ['required', 'string', 'regex:/^[a-z]{2}(-[A-Z]{2})?$/'],
            'subject_template' => ['required', 'string'],
            'body_template' => ['required', 'string'],
            'variables' => ['present', 'array'],
            'variables.*' => ['string', 'max:100'],
        ];
    }
}
