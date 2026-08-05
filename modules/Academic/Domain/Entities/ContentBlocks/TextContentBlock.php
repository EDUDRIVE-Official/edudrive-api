<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\ContentBlocks;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Parser\MarkdownParser;
use Modules\Academic\Domain\Enums\ContentBlockType;
use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\ExternalContentUrl;

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
        $environment = new Environment;
        $environment->addExtension(new CommonMarkCoreExtension);
        $walker = (new MarkdownParser($environment))->parse($markdown)->walker();

        while ($event = $walker->next()) {
            if (! $event->isEntering()) {
                continue;
            }

            $node = $event->getNode();

            if ($node instanceof HtmlBlock || $node instanceof HtmlInline) {
                return true;
            }

            if ($node instanceof Image) {
                return true;
            }

            if (
                $node instanceof Link
                && ! self::isValidLocalAnchor($node->getUrl())
                && ! self::isValidExternalTarget($node->getUrl())
            ) {
                return true;
            }
        }

        return false;
    }

    private static function isValidExternalTarget(string $target): bool
    {
        try {
            ExternalContentUrl::fromString($target);

            return true;
        } catch (InvalidContentBlock) {
            return false;
        }
    }

    private static function isValidLocalAnchor(string $target): bool
    {
        return str_starts_with($target, '#') && strlen($target) > 1;
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

        $markdown = preg_replace('/\A(?:[ \t]*\R)+/', '', $payload['markdown']) ?? $payload['markdown'];
        $markdown = preg_replace('/(?:\R[ \t]*)+\z/', '', $markdown) ?? $markdown;

        if (self::initialIndentationColumns($markdown) >= 4) {
            return rtrim($markdown);
        }

        return trim($markdown);
    }

    private static function initialIndentationColumns(string $markdown): int
    {
        $columns = 0;

        for ($index = 0, $length = strlen($markdown); $index < $length; $index++) {
            if ($markdown[$index] === ' ') {
                $columns++;

                continue;
            }

            if ($markdown[$index] === "\t") {
                $columns += 4 - ($columns % 4);

                continue;
            }

            break;
        }

        return $columns;
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
