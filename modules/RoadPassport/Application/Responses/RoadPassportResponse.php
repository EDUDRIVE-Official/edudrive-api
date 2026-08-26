<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\Responses;

use DateTimeInterface;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\ValueObjects\Evidence;
use Modules\RoadPassport\Domain\ValueObjects\PassportHistoryEntry;

final readonly class RoadPassportResponse
{
    /**
     * @param  list<array{type: string, from: string, to: string, occurred_at: string, reason: ?string}>  $history
     * @param  list<array{type: string, subject_id: string, course_id: string, occurred_at: string, details: array<string, mixed>}>  $evidence
     */
    public function __construct(
        public string $id,
        public string $userId,
        public string $status,
        public int $level,
        public string $issuedAt,
        public array $history,
        public array $evidence,
    ) {}

    public static function fromRoadPassport(RoadPassport $passport): self
    {
        return new self(
            id: $passport->id()->value(),
            userId: $passport->userId(),
            status: $passport->status()->value,
            level: $passport->level(),
            issuedAt: $passport->issuedAt()->format(DateTimeInterface::ATOM),
            history: array_map(
                static fn (PassportHistoryEntry $entry): array => [
                    'type' => $entry->type->value,
                    'from' => $entry->fromValue,
                    'to' => $entry->toValue,
                    'occurred_at' => $entry->occurredAt->format(DateTimeInterface::ATOM),
                    'reason' => $entry->reason,
                ],
                $passport->history(),
            ),
            evidence: array_map(
                static fn (Evidence $evidence): array => [
                    'type' => $evidence->type->value,
                    'subject_id' => $evidence->subjectId,
                    'course_id' => $evidence->courseId,
                    'occurred_at' => $evidence->occurredAt->format(DateTimeInterface::ATOM),
                    'details' => $evidence->details,
                ],
                $passport->evidence(),
            ),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     user_id: string,
     *     status: string,
     *     level: int,
     *     issued_at: string,
     *     history: list<array{type: string, from: string, to: string, occurred_at: string, reason: ?string}>,
     *     evidence: list<array{type: string, subject_id: string, course_id: string, occurred_at: string, details: array<string, mixed>}>,
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'status' => $this->status,
            'level' => $this->level,
            'issued_at' => $this->issuedAt,
            'history' => $this->history,
            'evidence' => $this->evidence,
        ];
    }
}
