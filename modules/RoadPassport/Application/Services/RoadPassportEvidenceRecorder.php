<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\Services;

use Modules\RoadPassport\Application\DTO\EvidenceEntry;

interface RoadPassportEvidenceRecorder
{
    public function record(EvidenceEntry $entry): void;
}
