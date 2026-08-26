<?php

declare(strict_types=1);

use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentEnrollmentRepository;

it('registra el repositorio de enrollments en el contenedor', function (): void {
    expect(app(EnrollmentRepository::class))->toBeInstanceOf(EloquentEnrollmentRepository::class);
});
