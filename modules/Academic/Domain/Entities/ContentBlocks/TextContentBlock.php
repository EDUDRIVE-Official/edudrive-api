<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\ContentBlocks;

use Modules\Academic\Domain\Enums\ContentBlockType;
use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;

final readonly class TextContentBlock implements ContentBlock
{
    private function __construct(
        private ContentBlockId $id,
        private int $position,
        private string $markdown,
        private ?string $title,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromPayload(ContentBlockId $id, int $position, array $payload): self
    {
        self::ensureValidPosition($position);
        self::ensureKeys($payload, ['markdown', 'title']);
        $markdown = self::requiredMarkdown($payload);

        if (self::containsRawHtml($markdown)) {
            throw InvalidContentBlock::create();
        }

        return new self($id, $position, $markdown, self::optionalString($payload, 'title'));
    }

    public function id(): ContentBlockId
    {
        return $this->id;
    }

    public function type(): ContentBlockType
    {
        return ContentBlockType::Text;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function payload(): array
    {
        return array_filter([
            'markdown' => $this->markdown,
            'title' => $this->title,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private static function ensureValidPosition(int $position): void
    {
        if ($position < 1) {
            throw InvalidContentBlock::create();
        }
    }

    private static function containsRawHtml(string $markdown): bool
    {
        $outsideFences = '';
        $fenceCharacter = null;
        $fenceLength = 0;
        $atBlockBoundary = true;
        $inIndentedCode = false;

        foreach (preg_split('/\R/', $markdown) ?: [] as $line) {
            if ($fenceCharacter === null && preg_match(self::openingFencePattern(), $line, $match) === 1) {
                $fenceCharacter = $match[1][0];
                $fenceLength = strlen($match[1]);

                continue;
            }

            if ($fenceCharacter !== null) {
                $closingPattern = sprintf(
                    '/^(?:[ \t]{0,3}>[ \t]?)*[ \t]{0,3}%s{%d,}[ \t]*$/',
                    preg_quote($fenceCharacter, '/'),
                    $fenceLength,
                );

                if (preg_match($closingPattern, $line) === 1) {
                    $fenceCharacter = null;
                    $fenceLength = 0;
                    $atBlockBoundary = true;
                }

                continue;
            }

            if (trim($line) === '') {
                $outsideFences .= "\n";
                $atBlockBoundary = true;

                continue;
            }

            if (
                preg_match('/^(?:[ \t]{0,3}>[ \t]?)*(?: {4}|\t)/', $line) === 1
                && ($atBlockBoundary || $inIndentedCode)
            ) {
                $inIndentedCode = true;

                continue;
            }

            $inIndentedCode = false;
            $atBlockBoundary = false;
            $outsideFences .= $line."\n";
        }

        $length = strlen($outsideFences);

        for ($index = 0; $index < $length; $index++) {
            $character = $outsideFences[$index];

            if ($character === '\\' && $index + 1 < $length) {
                $index++;

                continue;
            }

            if ($character === '`') {
                $runLength = strspn($outsideFences, '`', $index);
                $delimiter = str_repeat('`', $runLength);
                $closingPosition = strpos($outsideFences, $delimiter, $index + $runLength);

                if ($closingPosition !== false) {
                    $index = $closingPosition + $runLength - 1;

                    continue;
                }
            }

            if ($character !== '<') {
                continue;
            }

            $candidate = substr($outsideFences, $index);

            if (preg_match('/\A<https:\/\/[^\s<>]+>/i', $candidate, $match) === 1) {
                $index += strlen($match[0]) - 1;

                continue;
            }

            if (
                str_starts_with($candidate, '<!--')
                || preg_match('/\A<![A-Z]/i', $candidate) === 1
                || str_starts_with($candidate, '<?')
                || str_starts_with($candidate, '<![CDATA[')
                || preg_match('/\A<\s*\/?\s*[a-z][a-z0-9-]*(?:\s+[^<>]*?)?\s*\/?>/is', $candidate) === 1
            ) {
                return true;
            }
        }

        return false;
    }

    private static function openingFencePattern(): string
    {
        return '/^(?:[ \t]{0,3}>[ \t]?)*(?:[ \t]{0,3}(?:[-+*]|\d{1,9}[.)])[ \t]+)?[ \t]{0,3}(`{3,}|~{3,})/';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $allowed
     */
    private static function ensureKeys(array $payload, array $allowed): void
    {
        if (array_diff(array_keys($payload), $allowed) !== []) {
            throw InvalidContentBlock::create();
        }
    }

    /** @param array<string, mixed> $payload */
    private static function requiredMarkdown(array $payload): string
    {
        if (! isset($payload['markdown']) || ! is_string($payload['markdown']) || trim($payload['markdown']) === '') {
            throw InvalidContentBlock::create();
        }

        if (preg_match('/^(?: {4}|\t)/', $payload['markdown']) === 1) {
            return rtrim($payload['markdown']);
        }

        return trim($payload['markdown']);
    }

    /** @param array<string, mixed> $payload */
    private static function optionalString(array $payload, string $key): ?string
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null || $payload[$key] === '') {
            return null;
        }
        if (! is_string($payload[$key])) {
            throw InvalidContentBlock::create();
        }

        $value = trim($payload[$key]);

        return $value === '' ? null : $value;
    }
}
