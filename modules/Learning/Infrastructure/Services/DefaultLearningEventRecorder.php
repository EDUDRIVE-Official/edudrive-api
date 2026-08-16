<?php

declare(strict_types=1);

namespace Modules\Learning\Infrastructure\Services;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Learning\Application\DTO\LearningEventEntry;
use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Domain\ValueObjects\LearningEventId;

final readonly class DefaultLearningEventRecorder implements LearningEventRecorder
{
    public function __construct(private LearningEventRepository $events) {}

    public function record(LearningEventEntry $entry): void
    {
        $this->events->record(LearningEvent::create(
            id: LearningEventId::fromString((string) Str::uuid()),
            enrollmentId: $entry->enrollmentId,
            userId: $entry->userId,
            courseId: $entry->courseId,
            verb: $entry->verb,
            subjectId: $entry->subjectId,
            occurredAt: new DateTimeImmutable('now'),
            evidence: $entry->evidence,
        ));
    }
}
