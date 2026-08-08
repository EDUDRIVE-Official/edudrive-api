<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\ContentBlocks;

use Modules\Academic\Domain\Enums\ContentBlockType;
use Modules\Academic\Domain\Exceptions\ContentAccessibilityRequired;
use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\ExternalContentUrl;
use Modules\Academic\Domain\ValueObjects\MimeType;

final readonly class DownloadContentBlock implements ContentBlock
{
    private function __construct(
        private ContentBlockId $id,
        private int $position,
        private ExternalContentUrl $url,
        private string $displayName,
        private MimeType $mimeType,
        private ?string $description,
        private ?string $filename,
        private ?int $sizeBytes,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromPayload(ContentBlockId $id, int $position, array $payload): self
    {
        self::ensureValidPosition($position);
        self::ensureKeys($payload, ['url', 'display_name', 'mime_type', 'description', 'filename', 'size_bytes']);
        $displayName = self::accessibleString($payload, 'display_name');
        $sizeBytes = $payload['size_bytes'] ?? null;

        if ($sizeBytes !== null && (! is_int($sizeBytes) || $sizeBytes < 1)) {
            throw InvalidContentBlock::create();
        }

        return new self(
            $id,
            $position,
            ExternalContentUrl::fromString(self::requiredString($payload, 'url')),
            $displayName,
            MimeType::fromString(self::requiredString($payload, 'mime_type')),
            self::optionalString($payload, 'description'),
            self::optionalString($payload, 'filename'),
            $sizeBytes,
        );
    }

    public function id(): ContentBlockId
    {
        return $this->id;
    }

    public function type(): ContentBlockType
    {
        return ContentBlockType::Download;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function payload(): array
    {
        return array_filter([
            'url' => $this->url->value(),
            'display_name' => $this->displayName,
            'mime_type' => $this->mimeType->value(),
            'description' => $this->description,
            'filename' => $this->filename,
            'size_bytes' => $this->sizeBytes,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private static function ensureValidPosition(int $position): void
    {
        if ($position < 1) {
            throw InvalidContentBlock::create();
        }
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
    private static function requiredString(array $payload, string $key): string
    {
        if (! isset($payload[$key]) || ! is_string($payload[$key]) || trim($payload[$key]) === '') {
            throw InvalidContentBlock::create();
        }

        return trim($payload[$key]);
    }

    /** @param array<string, mixed> $payload */
    private static function accessibleString(array $payload, string $key): string
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            throw ContentAccessibilityRequired::forField($key);
        }

        if (! is_string($payload[$key])) {
            throw InvalidContentBlock::create();
        }

        if (trim($payload[$key]) === '') {
            throw ContentAccessibilityRequired::forField($key);
        }

        return trim($payload[$key]);
    }

    /** @param array<string, mixed> $payload */
    private static function optionalString(array $payload, string $key): ?string
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }
        if (! is_string($payload[$key])) {
            throw InvalidContentBlock::create();
        }
        $value = trim($payload[$key]);

        return $value === '' ? null : $value;
    }
}
