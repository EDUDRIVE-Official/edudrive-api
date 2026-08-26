<?php

declare(strict_types=1);

namespace Modules\Gamification\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Gamification\Domain\Enums\BadgeCategory;
use Modules\Gamification\Domain\Enums\BadgeLevel;

final class CreateBadgeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'criteria' => ['required', 'string'],
            'category' => ['required', 'string', Rule::in(array_map(
                static fn (BadgeCategory $category): string => $category->value,
                BadgeCategory::cases(),
            ))],
            'level' => ['required', 'string', Rule::in(array_map(
                static fn (BadgeLevel $level): string => $level->value,
                BadgeLevel::cases(),
            ))],
        ];
    }
}
