<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Foundation\Domain\Exceptions\DomainException;

final class ReplaceUnitContentRequest extends FormRequest
{
    private const MAX_LESSONS = 100;

    private const MAX_BLOCKS_PER_LESSON = 200;

    private const MAX_TOTAL_BLOCKS = 1000;

    private const MAX_LONG_TEXT = 50000;

    private bool $aggregateLimitExceeded = false;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        if ($this->aggregateLimitExceeded) {
            return [
                'lessons' => ['present', 'array', static function (string $attribute, mixed $value, Closure $fail): void {
                    $fail('The lessons field exceeds an aggregate content limit.');
                }],
            ];
        }

        $lessons = $this->input('lessons');
        $rules = [
            'lessons' => ['present', 'array', 'list', 'max:'.self::MAX_LESSONS],
            'lessons.*' => ['array:id,code,title,summary,duration_minutes,position,blocks'],
            'lessons.*.id' => ['bail', 'required', 'uuid', $this->distinctIgnoringCase($this->valuesForKey($lessons, 'id'))],
            'lessons.*.code' => ['bail', 'required', 'string', 'max:60', 'regex:/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/', $this->distinctIgnoringCase($this->valuesForKey($lessons, 'code'))],
            'lessons.*.title' => ['required', 'string', 'max:180'],
            'lessons.*.summary' => ['nullable', 'string', 'max:'.self::MAX_LONG_TEXT],
            'lessons.*.duration_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'lessons.*.position' => ['required', 'integer', 'min:1', 'max:1000000'],
            'lessons.*.blocks' => ['present', 'array', 'list', 'max:'.self::MAX_BLOCKS_PER_LESSON],
            'lessons.*.blocks.*' => ['array:id,type,position,payload'],
            'lessons.*.blocks.*.id' => ['bail', 'required', 'uuid', $this->distinctIgnoringCase($this->allBlockValues($lessons, 'id'))],
            'lessons.*.blocks.*.type' => ['required', 'string', 'in:text,image,video,audio,interactive,download'],
            'lessons.*.blocks.*.position' => ['required', 'integer', 'min:1', 'max:1000000'],
            'lessons.*.blocks.*.payload' => ['present', 'array'],
        ];

        if (! is_array($lessons)) {
            return $rules;
        }

        foreach ($lessons as $lessonIndex => $lesson) {
            if (! is_int($lessonIndex) || ! is_array($lesson) || ! is_array($lesson['blocks'] ?? null)) {
                continue;
            }

            foreach ($lesson['blocks'] as $blockIndex => $block) {
                if (! is_int($blockIndex) || ! is_array($block)) {
                    continue;
                }

                $prefix = "lessons.{$lessonIndex}.blocks.{$blockIndex}";
                $type = $block['type'] ?? null;
                $payload = $block['payload'] ?? null;
                $rules["{$prefix}.payload"] = [
                    'present',
                    'array'.($this->allowedPayloadKeys($type) === [] ? '' : ':'.implode(',', $this->allowedPayloadKeys($type))),
                    $this->validDomainPayload($block),
                ];

                if (! is_array($payload)) {
                    continue;
                }

                foreach ($this->payloadRules($type) as $key => $payloadRules) {
                    $rules["{$prefix}.payload.{$key}"] = $payloadRules;
                }
            }
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $lessons = $this->input('lessons');

        if (! is_array($lessons)) {
            return;
        }

        if (count($lessons) > self::MAX_LESSONS) {
            $this->aggregateLimitExceeded = true;

            return;
        }

        $totalBlocks = 0;
        foreach ($lessons as $lesson) {
            if (! is_array($lesson) || ! is_array($lesson['blocks'] ?? null)) {
                continue;
            }

            $blockCount = count($lesson['blocks']);
            if ($blockCount > self::MAX_BLOCKS_PER_LESSON) {
                $this->aggregateLimitExceeded = true;

                return;
            }

            $totalBlocks += $blockCount;
            if ($totalBlocks > self::MAX_TOTAL_BLOCKS) {
                $this->aggregateLimitExceeded = true;

                return;
            }
        }
    }

    /** @return list<string> */
    private function allowedPayloadKeys(mixed $type): array
    {
        return match ($type) {
            'text' => ['markdown', 'title'],
            'image' => ['url', 'alt', 'caption'],
            'video' => ['url', 'captions_url', 'transcript', 'title', 'description'],
            'audio' => ['url', 'transcript', 'title', 'description'],
            'interactive' => ['url', 'accessible_text', 'accessible_url', 'title', 'description'],
            'download' => ['url', 'display_name', 'mime_type', 'description', 'filename', 'size_bytes'],
            default => [],
        };
    }

    /** @return array<string, array<int, mixed>> */
    private function payloadRules(mixed $type): array
    {
        $url = ['required', 'string', 'max:2048', $this->httpsUrl()];
        $optionalUrl = ['nullable', 'string', 'max:2048', $this->httpsUrl()];
        $longRequired = ['required', 'string', 'max:'.self::MAX_LONG_TEXT];
        $longOptional = ['nullable', 'string', 'max:'.self::MAX_LONG_TEXT];
        $title = ['nullable', 'string', 'max:180'];

        return match ($type) {
            'text' => ['markdown' => $longRequired, 'title' => $title],
            'image' => ['url' => $url, 'alt' => $longRequired, 'caption' => $longOptional],
            'video' => ['url' => $url, 'captions_url' => $url, 'transcript' => $longRequired, 'title' => $title, 'description' => $longOptional],
            'audio' => ['url' => $url, 'transcript' => $longRequired, 'title' => $title, 'description' => $longOptional],
            'interactive' => ['url' => $url, 'accessible_text' => $longOptional, 'accessible_url' => $optionalUrl, 'title' => $title, 'description' => $longOptional],
            'download' => ['url' => $url, 'display_name' => ['required', 'string', 'max:180'], 'mime_type' => ['required', 'string', 'max:255'], 'description' => $longOptional, 'filename' => ['nullable', 'string', 'max:255'], 'size_bytes' => ['nullable', 'integer', 'min:1']],
            default => [],
        };
    }

    private function httpsUrl(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value)) {
                return;
            }

            $parts = parse_url($value);
            if ($parts === false || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || ($parts['host'] ?? '') === '') {
                $fail("The {$attribute} field must be a valid HTTPS URL.");
            }
        };
    }

    /** @param array<string, mixed> $block */
    private function validDomainPayload(array $block): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($block): void {
            if (! is_array($value)
                || ! is_string($block['id'] ?? null)
                || ! is_string($block['type'] ?? null)
                || ! is_int($block['position'] ?? null)) {
                return;
            }

            try {
                ContentBlockFactory::create(
                    ContentBlockId::fromString($block['id']),
                    $block['type'],
                    $block['position'],
                    $value,
                );
            } catch (DomainException|InvalidArgumentException) {
                $fail("The {$attribute} field contains invalid content.");
            }
        };
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
    private function allBlockValues(mixed $lessons, string $key): array
    {
        if (! is_array($lessons)) {
            return [];
        }

        $values = [];
        foreach ($lessons as $lesson) {
            if (is_array($lesson)) {
                array_push($values, ...$this->valuesForKey($lesson['blocks'] ?? null, $key));
            }
        }

        return $values;
    }

    private function distinctIgnoringCase(mixed $siblings): Closure
    {
        $counts = [];
        if (is_array($siblings)) {
            foreach ($siblings as $sibling) {
                if (is_string($sibling)) {
                    $normalized = mb_strtolower($sibling);
                    $counts[$normalized] = ($counts[$normalized] ?? 0) + 1;
                }
            }
        }

        return static function (string $attribute, mixed $value, Closure $fail) use ($counts): void {
            if (is_string($value) && ($counts[mb_strtolower($value)] ?? 0) > 1) {
                $fail("The {$attribute} field has a duplicate value.");
            }
        };
    }
}
