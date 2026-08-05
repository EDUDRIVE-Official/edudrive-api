<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\ContentBlocks;

use Modules\Academic\Domain\Enums\ContentBlockType;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;

interface ContentBlock
{
    public function id(): ContentBlockId;

    public function type(): ContentBlockType;

    public function position(): int;

    /** @return array<string, mixed> */
    public function payload(): array;
}
