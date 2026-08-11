<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;

final readonly class QuestionMedia
{
    private const array ALLOWED_TYPES = ['image', 'video', 'audio'];

    private function __construct(
        public string $type,
        public string $url,
    ) {}

    /** @param array{type: mixed, url: mixed} $data */
    public static function fromArray(array $data): self
    {
        $type = is_string($data['type'] ?? null) ? trim($data['type']) : '';
        $url = is_string($data['url'] ?? null) ? trim($data['url']) : '';

        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            throw InvalidQuestion::create();
        }

        try {
            ExternalContentUrl::fromString($url);
        } catch (InvalidContentBlock) {
            throw InvalidQuestion::create();
        }

        return new self($type, $url);
    }

    /** @return array{type: string, url: string} */
    public function toArray(): array
    {
        return ['type' => $this->type, 'url' => $this->url];
    }
}