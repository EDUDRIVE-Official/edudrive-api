<?php

declare(strict_types=1);

use Modules\Foundation\Infrastructure\Export\CsvWriter;

it('escribe encabezados y filas como texto csv', function (): void {
    $csv = (new CsvWriter)->toString(
        ['id', 'name'],
        [
            ['1', 'Ana'],
            ['2', 'Carlos'],
        ],
    );

    expect($csv)->toBe("id,name\n1,Ana\n2,Carlos\n");
});

it('escribe unicamente los encabezados cuando no hay filas', function (): void {
    $csv = (new CsvWriter)->toString(['id', 'name'], []);

    expect($csv)->toBe("id,name\n");
});

it('escapa valores que contienen comas o comillas', function (): void {
    $csv = (new CsvWriter)->toString(
        ['label'],
        [['Contiene, una coma'], ['Contiene "comillas"']],
    );

    expect($csv)->toBe("label\n\"Contiene, una coma\"\n\"Contiene \"\"comillas\"\"\"\n");
});
