<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum ContentBlockType: string
{
    case Text = 'text';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Interactive = 'interactive';
    case Download = 'download';
}
