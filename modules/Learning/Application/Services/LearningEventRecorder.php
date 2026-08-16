<?php

declare(strict_types=1);

namespace Modules\Learning\Application\Services;

use Modules\Learning\Application\DTO\LearningEventEntry;

interface LearningEventRecorder
{
    public function record(LearningEventEntry $entry): void;
}
