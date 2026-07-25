<?php

declare(strict_types=1);

use Tests\TestCase;

pest()
    ->extend(TestCase::class)
    ->in(
        'Feature',
        '../modules/*/Tests/Unit',
        '../modules/*/Tests/Feature',
        '../modules/*/Tests/Integration',
    );
