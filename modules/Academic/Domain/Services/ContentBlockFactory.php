<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Services;

use Modules\Academic\Domain\Entities\ContentBlocks\AudioContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\ContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\DownloadContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\ImageContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\InteractiveContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\TextContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\VideoContentBlock;
use Modules\Academic\Domain\Enums\ContentBlockType;
use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;

final class ContentBlockFactory
{
    /** @param array<string, mixed> $payload */
    public static function create(
        ContentBlockId $id,
        ContentBlockType|string $type,
        int $position,
        array $payload,
    ): ContentBlock {
        if (is_string($type)) {
            $type = ContentBlockType::tryFrom($type) ?? throw InvalidContentBlock::create();
        }

        if ($position < 1) {
            throw InvalidContentBlock::create();
        }

        return match ($type) {
            ContentBlockType::Text => TextContentBlock::fromPayload($id, $position, $payload),
            ContentBlockType::Image => ImageContentBlock::fromPayload($id, $position, $payload),
            ContentBlockType::Video => VideoContentBlock::fromPayload($id, $position, $payload),
            ContentBlockType::Audio => AudioContentBlock::fromPayload($id, $position, $payload),
            ContentBlockType::Interactive => InteractiveContentBlock::fromPayload($id, $position, $payload),
            ContentBlockType::Download => DownloadContentBlock::fromPayload($id, $position, $payload),
        };
    }
}
