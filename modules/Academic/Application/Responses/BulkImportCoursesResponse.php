<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class BulkImportCoursesResponse
{
    /** @param list<array{row: int, created: bool, course_id?: string, code?: string, error_code?: string}> $results */
    public function __construct(
        public int $total,
        public int $created,
        public int $failed,
        public array $results,
    ) {}

    /** @return array{total: int, created: int, failed: int, results: list<array{row: int, created: bool, course_id?: string, code?: string, error_code?: string}>} */
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
