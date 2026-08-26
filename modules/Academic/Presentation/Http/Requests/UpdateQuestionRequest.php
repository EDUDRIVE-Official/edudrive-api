<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;
use Modules\Academic\Domain\Enums\QuestionSourceKind;
use Modules\Academic\Domain\Enums\QuestionType;

final class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', new Enum(QuestionType::class)],
            'prompt' => ['required', 'string', 'max:1000'],
            'score' => ['required', 'integer', 'min:1'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'source_kind' => ['sometimes', 'string', new Enum(QuestionSourceKind::class)],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'license_categories' => ['sometimes', 'array'],
            'license_categories.*' => ['required', 'string', 'regex:/\S/'],
            'options' => ['sometimes', 'array'],
            'options.*.ref_id' => ['required_with:options', 'distinct', 'string', 'max:80'],
            'options.*.label' => ['required_with:options', 'string', 'max:500'],
            'options.*.side' => ['nullable', 'string', new In(['left', 'right'])],
            'media' => ['sometimes', 'array'],
            'media.*.type' => ['required_with:media', 'string', new In(['image', 'video', 'audio'])],
            'media.*.url' => ['required_with:media', 'string', 'url'],
            'response' => ['required', 'array'],
        ];
    }
}
