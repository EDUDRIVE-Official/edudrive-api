<?php

declare(strict_types=1);

namespace Modules\Notification\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PreviewCommunicationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'variables' => ['present', 'array'],
            'variables.*' => ['string'],
        ];
    }
}
