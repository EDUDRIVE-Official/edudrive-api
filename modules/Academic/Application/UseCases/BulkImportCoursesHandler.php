<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use InvalidArgumentException;
use Modules\Academic\Application\Commands\BulkImportCoursesCommand;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Responses\BulkImportCoursesResponse;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Throwable;

final readonly class BulkImportCoursesHandler
{
    public function __construct(
        private CourseRepository $courses,
    ) {}

    public function handle(BulkImportCoursesCommand $command): BulkImportCoursesResponse
    {
        $created = 0;
        $failed = 0;
        $results = [];

        foreach ($command->rows as $index => $row) {
            $rowNumber = $index + 1;

            try {
                $results[] = $this->importRow($rowNumber, $row);
                $created++;
            } catch (DomainException $e) {
                $failed++;
                $results[] = ['row' => $rowNumber, 'created' => false, 'error_code' => $e->errorCode()];
            } catch (Throwable) {
                $failed++;
                $results[] = ['row' => $rowNumber, 'created' => false, 'error_code' => 'IMPORT_ROW_INVALID'];
            }
        }

        return new BulkImportCoursesResponse(
            total: count($command->rows),
            created: $created,
            failed: $failed,
            results: $results,
        );
    }

    /**
     * @param  array{code: string, title: string, description: string, objectives: string, prerequisites: string, modality: string, duration_hours: string}  $row
     * @return array{row: int, created: bool, course_id: string, code: string}
     */
    private function importRow(int $rowNumber, array $row): array
    {
        $code = trim($row['code']);
        $title = trim($row['title']);

        if ($code === '' || $title === '') {
            throw new InvalidArgumentException('Fila incompleta: se requieren code y title.');
        }

        $durationHours = trim($row['duration_hours']);
        if ($durationHours !== '' && ! ctype_digit($durationHours)) {
            throw new InvalidArgumentException('duration_hours debe ser un entero.');
        }

        $response = (new CreateCourseHandler($this->courses))->handle(new CreateCourseCommand(
            code: $code,
            title: $title,
            description: $this->nullableString($row['description']),
            objectives: $this->nullableString($row['objectives']),
            prerequisites: $this->nullableString($row['prerequisites']),
            modality: $this->nullableString($row['modality']),
            durationHours: $durationHours === '' ? null : (int) $durationHours,
        ));

        return [
            'row' => $rowNumber,
            'created' => true,
            'course_id' => $response->id,
            'code' => $response->code,
        ];
    }

    private function nullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
