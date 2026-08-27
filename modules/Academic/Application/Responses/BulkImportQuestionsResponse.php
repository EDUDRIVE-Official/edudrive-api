<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class BulkImportQuestionsResponse
{
    /** @param list<array{row: int, created: bool, question_id?: string, error_code?: string}> $results */
    public function __construct(
        public int $total,
        public int $created,
        public int $failed,
        public array $results,
    ) {}

    /** @return array{total: int, created: int, failed: int, results: list<array{row: int, created: bool, question_id?: string, error_code?: string}>} */
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
