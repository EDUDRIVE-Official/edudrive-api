<?php

declare(strict_types=1);

namespace Modules\Mobile\Domain\Enums;

enum DevicePlatform: string
{
    case Ios = 'ios';
    case Android = 'android';
}
