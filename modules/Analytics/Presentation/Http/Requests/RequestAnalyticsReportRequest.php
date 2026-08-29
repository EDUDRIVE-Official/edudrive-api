<?php

declare(strict_types=1);

namespace Modules\Analytics\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RequestAnalyticsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string'],
        ];
    }
}
