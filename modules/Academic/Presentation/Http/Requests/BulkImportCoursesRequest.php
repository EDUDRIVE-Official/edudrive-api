<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BulkImportCoursesRequest extends FormRequest
{
    private const int MAX_FILE_SIZE_KB = 2048;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:'.self::MAX_FILE_SIZE_KB],
        ];
    }
}
