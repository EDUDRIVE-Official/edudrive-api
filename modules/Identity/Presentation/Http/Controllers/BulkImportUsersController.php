<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;
use Modules\Identity\Application\Commands\BulkImportUsersCommand;
use Modules\Identity\Application\UseCases\BulkImportUsersUseCase;
use Modules\Identity\Presentation\Http\Requests\BulkImportUsersRequest;

final class BulkImportUsersController extends Controller
{
    private const int MAX_ROWS = 500;

    public function __construct(
        private readonly BulkImportUsersUseCase $useCase,
    ) {}

    public function __invoke(BulkImportUsersRequest $request): JsonResponse
    {
        $file = $request->file('file');
        assert($file !== null);

        $csv = Reader::createFromPath((string) $file->getRealPath());
        $csv->setHeaderOffset(0);

        $rows = [];
        foreach ($csv->getRecords() as $record) {
            $rows[] = [
                'name' => (string) ($record['name'] ?? ''),
                'email' => (string) ($record['email'] ?? ''),
                'password' => (string) ($record['password'] ?? ''),
                'role' => (string) ($record['role'] ?? ''),
            ];
        }

        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages([
                'file' => [sprintf('El archivo no puede contener más de %d filas.', self::MAX_ROWS)],
            ]);
        }

        $result = $this->useCase->execute(new BulkImportUsersCommand(rows: $rows));

        return response()->json(['data' => $result->toArray()]);
    }
}
