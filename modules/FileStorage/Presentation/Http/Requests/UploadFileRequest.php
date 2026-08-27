<?php

declare(strict_types=1);

namespace Modules\FileStorage\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadFileRequest extends FormRequest
{
    private const int MAX_FILE_SIZE_KB = 20 * 1024;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.self::MAX_FILE_SIZE_KB],
        ];
    }
}
