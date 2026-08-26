<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Infrastructure\Services;

use DateTimeImmutable;
use Modules\RoadPassport\Application\DTO\EvidenceEntry;
use Modules\RoadPassport\Application\Services\RoadPassportEvidenceRecorder;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\Evidence;

final readonly class DefaultRoadPassportEvidenceRecorder implements RoadPassportEvidenceRecorder
{
    public function __construct(private RoadPassportRepository $passports) {}

    public function record(EvidenceEntry $entry): void
    {
        $passport = $this->passports->findByUserId($entry->userId);
        if ($passport === null) {
            return;
        }

        $passport->recordEvidence(Evidence::create(
            $entry->type,
            $entry->subjectId,
            $entry->courseId,
            new DateTimeImmutable('now'),
            $entry->details,
        ));

        $this->passports->save($passport);
    }
}
