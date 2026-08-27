<?php

declare(strict_types=1);

namespace Modules\FileStorage\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\FileStorage\Domain\Enums\FileScanStatus;

final class SetFileScanStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'scan_status' => ['required', 'string', Rule::in(array_map(
                static fn (FileScanStatus $status): string => $status->value,
                FileScanStatus::cases(),
            ))],
        ];
    }
}
