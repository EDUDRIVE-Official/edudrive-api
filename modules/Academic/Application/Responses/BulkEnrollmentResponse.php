<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class BulkEnrollmentResponse
{
    /** @param list<array{user_id: string, created: bool, enrollment_id?: string, error_code?: string}> $results */
    public function __construct(
        public int $total,
        public int $created,
        public int $failed,
        public array $results,
    ) {}

    /** @return array{total: int, created: int, failed: int, results: list<array{user_id: string, created: bool, enrollment_id?: string, error_code?: string}>} */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'created' => $this->created,
            'failed' => $this->failed,
            'results' => $this->results,
        ];
    }
}
