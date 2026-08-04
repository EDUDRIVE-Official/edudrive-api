<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

final class ReplaceCourseCurriculumRequest extends FormRequest
{
    private const MAX_TOTAL_MODULES = 200;

    private const MAX_TOTAL_UNITS = 1000;

    private const MAX_TOTAL_PREREQUISITE_REFERENCES = 5000;

    private bool $totalModulesExceeded = false;

    private bool $totalUnitsExceeded = false;

    private bool $totalPrerequisiteReferencesExceeded = false;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $modules = $this->input('modules');

        if ($this->totalModulesExceeded
            || $this->totalUnitsExceeded
            || $this->totalPrerequisiteReferencesExceeded) {
            return [
                'modules' => [
                    'present',
                    'array',
                    function (string $attribute, mixed $value, Closure $fail): void {
                        if ($this->totalModulesExceeded) {
                            $fail('The modules field must not contain more than 200 modules.');
                        }

                        if ($this->totalUnitsExceeded) {
                            $fail('The modules field must not contain more than 1000 units in total.');
                        }

                        if ($this->totalPrerequisiteReferencesExceeded) {
                            $fail('The modules field must not contain more than 5000 prerequisite references in total.');
                        }
                    },
                ],
            ];
        }

        $rules = [
            'modules' => ['present', 'array', 'max:200'],
            'modules.*.id' => [
                'bail',
                'required',
                'uuid',
                $this->distinctIgnoringCaseRule($this->valuesForKey($modules, 'id')),
            ],
            'modules.*.code' => [
                'bail',
                'required',
                'string',
                'max:60',
                'regex:/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/',
                $this->distinctIgnoringCaseRule($this->valuesForKey($modules, 'code')),
            ],
            'modules.*.title' => ['required', 'string', 'max:180'],
            'modules.*.description' => ['required', 'string', 'max:5000'],
            'modules.*.objectives' => ['nullable', 'string', 'max:5000'],
            'modules.*.duration_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'modules.*.position' => ['required', 'integer', 'min:1', 'max:1000000'],
            'modules.*.prerequisite_module_ids' => ['present', 'array', 'max:200'],
            'modules.*.prerequisite_module_ids.*' => ['required', 'uuid'],
            'modules.*.units' => ['present', 'array', 'max:500'],
            'modules.*.units.*.id' => [
                'bail',
                'required',
                'uuid',
                $this->distinctIgnoringCaseRule($this->nestedValuesForKey($modules, 'units', 'id')),
            ],
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

    protected function prepareForValidation(): void
    {
        $modules = $this->input('modules');

        if (! is_array($modules)) {
            return;
        }

        if (count($modules) > self::MAX_TOTAL_MODULES) {
            $this->totalModulesExceeded = true;

            return;
        }

        $totalUnits = 0;
        $totalPrerequisiteReferences = 0;

        foreach ($modules as $module) {
            if (! is_array($module)) {
                continue;
            }

            $modulePrerequisites = $module['prerequisite_module_ids'] ?? null;

            if (is_array($modulePrerequisites)) {
                $totalPrerequisiteReferences += count($modulePrerequisites);

                if ($totalPrerequisiteReferences > self::MAX_TOTAL_PREREQUISITE_REFERENCES) {
                    $this->totalPrerequisiteReferencesExceeded = true;

                    return;
                }
            }

            $units = $module['units'] ?? null;

            if (! is_array($units)) {
                continue;
            }

            $totalUnits += count($units);

            if ($totalUnits > self::MAX_TOTAL_UNITS) {
                $this->totalUnitsExceeded = true;

                return;
            }

            foreach ($units as $unit) {
                if (! is_array($unit)) {
                    continue;
                }

                $unitPrerequisites = $unit['prerequisite_unit_ids'] ?? null;

                if (! is_array($unitPrerequisites)) {
                    continue;
                }

                $totalPrerequisiteReferences += count($unitPrerequisites);

                if ($totalPrerequisiteReferences > self::MAX_TOTAL_PREREQUISITE_REFERENCES) {
                    $this->totalPrerequisiteReferencesExceeded = true;

                    return;
                }
            }
        }
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

    /** @return list<mixed> */
    private function nestedValuesForKey(
        mixed $containers,
        string $itemsKey,
        string $valueKey,
    ): array {
        if (! is_array($containers)) {
            return [];
        }

        $values = [];

        foreach ($containers as $container) {
            if (! is_array($container)) {
                continue;
            }

            array_push(
                $values,
                ...$this->valuesForKey($container[$itemsKey] ?? null, $valueKey),
            );
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
