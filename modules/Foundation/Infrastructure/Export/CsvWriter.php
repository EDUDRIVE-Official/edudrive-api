<?php

declare(strict_types=1);

namespace Modules\Foundation\Infrastructure\Export;

use League\Csv\Writer;

final class CsvWriter
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public function toString(array $headers, array $rows): string
    {
        $csv = Writer::createFromString('');
        $csv->insertOne($headers);
        $csv->insertAll($rows);

        return $csv->toString();
    }
}
