<?php

declare(strict_types=1);

use Modules\Academic\Domain\ValueObjects\ExamAttemptId;

it('construye un ExamAttemptId desde un UUID válido', function (): void {
    $id = ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92001');
    expect($id->value())->toBe('01981a64-8300-7b1d-b442-764ea7f92001');
});

it('rechaza un ExamAttemptId no UUID', function (): void {
    expect(fn () => ExamAttemptId::fromString('no-uuid'))->toThrow(InvalidArgumentException::class);
});
