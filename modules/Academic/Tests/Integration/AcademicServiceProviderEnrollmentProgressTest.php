<?php

declare(strict_types=1);

use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentEnrollmentProgressRepository;

it('registra el repositorio de progreso de enrollment en el contenedor', function (): void {
    expect(app(EnrollmentProgressRepository::class))->toBeInstanceOf(EloquentEnrollmentProgressRepository::class);
});
