<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

final class ReplaceCourseCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = [
            'modules' => ['present', 'array', 'max:200'],
            'modules.*.id' => ['required', 'uuid', 'distinct:ignore_case'],
            'modules.*.code' => [
                'required',
                'string',
                'max:60',
                'regex:/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/',
                'distinct:ignore_case',
            ],
            'modules.*.title' => ['required', 'string', 'max:180'],
            'modules.*.description' => ['required', 'string', 'max:5000'],
            'modules.*.objectives' => ['nullable', 'string', 'max:5000'],
            'modules.*.duration_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'modules.*.position' => ['required', 'integer', 'min:1', 'max:1000000'],
            'modules.*.prerequisite_module_ids' => ['present', 'array', 'max:200'],
            'modules.*.prerequisite_module_ids.*' => ['required', 'uuid'],
            'modules.*.units' => ['present', 'array', 'max:500'],
            'modules.*.units.*.id' => ['required', 'uuid', 'distinct:ignore_case'],
            'modules.*.units.*.code' => [
                'required',
                'string',
                'max:60',
                'regex:/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/',
            ],
            'modules.*.units.*.title' => ['required', 'string', 'max:180'],
            'modules.*.units.*.description' => ['required', 'string', 'max:5000'],
            'modules.*.units.*.objectives' => ['nullable', 'string', 'max:5000'],
            'modules.*.units.*.duration_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'modules.*.units.*.position' => ['required', 'integer', 'min:1', 'max:1000000'],
            'modules.*.units.*.prerequisite_unit_ids' => ['present', 'array', 'max:200'],
            'modules.*.units.*.prerequisite_unit_ids.*' => ['required', 'uuid'],
        ];

        $modules = $this->input('modules');

        if (! is_array($modules)) {
            return $rules;
        }

        foreach ($modules as $moduleIndex => $module) {
            if (! is_int($moduleIndex) || ! is_array($module)) {
                continue;
            }

            $rules["modules.{$moduleIndex}.prerequisite_module_ids.*"] = [
                'required',
                'uuid',
                $this->distinctIgnoringCaseRule($module['prerequisite_module_ids'] ?? null),
            ];
            $units = $module['units'] ?? null;
            $rules["modules.{$moduleIndex}.units.*.code"] = [
                'required',
                'string',
                'max:60',
                'regex:/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/',
                $this->distinctIgnoringCaseRule($this->valuesForKey($units, 'code')),
            ];

            if (! is_array($units)) {
                continue;
            }

            foreach ($units as $unitIndex => $unit) {
                if (! is_int($unitIndex) || ! is_array($unit)) {
                    continue;
                }

                $rules["modules.{$moduleIndex}.units.{$unitIndex}.prerequisite_unit_ids.*"] = [
                    'required',
                    'uuid',
                    $this->distinctIgnoringCaseRule($unit['prerequisite_unit_ids'] ?? null),
                ];
            }
        }

        return $rules;
    }

    /** @return list<mixed> */
    private function valuesForKey(mixed $items, string $key): array
    {
        if (! is_array($items)) {
            return [];
        }

        $values = [];

        foreach ($items as $item) {
            if (is_array($item) && array_key_exists($key, $item)) {
                $values[] = $item[$key];
            }
        }

        return $values;
    }

    private function distinctIgnoringCaseRule(mixed $siblings): Closure
    {
        $counts = [];

        if (is_array($siblings)) {
            foreach ($siblings as $sibling) {
                if (! is_string($sibling)) {
                    continue;
                }

                $normalized = mb_strtolower($sibling);
                $counts[$normalized] = ($counts[$normalized] ?? 0) + 1;
            }
        }

        return static function (string $attribute, mixed $value, Closure $fail) use ($counts): void {
            if (! is_string($value)) {
                return;
            }

            if (($counts[mb_strtolower($value)] ?? 0) > 1) {
                $fail("The {$attribute} field has a duplicate value.");
            }
        };
    }
}
