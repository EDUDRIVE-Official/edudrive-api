<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

it('exporta cursos a csv con el permiso exports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    app(CourseRepository::class)->save(Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EXPF-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso feature para exportar'),
    ));

    $this->postJson('/api/v1/academic/courses/export')
        ->assertOk()
        ->assertJsonPath('data.format', 'csv')
        ->assertJsonStructure(['data' => ['url', 'expires_at', 'row_count', 'format']]);
});

it('rechaza exportar cursos sin el permiso exports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->postJson('/api/v1/academic/courses/export')->assertForbidden();
});

it('requiere autenticacion para exportar cursos', function (): void {
    /** @var TestCase $this */
    $this->postJson('/api/v1/academic/courses/export')->assertUnauthorized();
});

it('exporta enrollments a csv con el permiso exports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/enrollments/export')
        ->assertOk()
        ->assertJsonPath('data.format', 'csv')
        ->assertJsonStructure(['data' => ['url', 'expires_at', 'row_count', 'format']]);
});

it('rechaza exportar enrollments sin el permiso exports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->postJson('/api/v1/academic/enrollments/export')->assertForbidden();
});

it('requiere autenticacion para exportar enrollments', function (): void {
    /** @var TestCase $this */
    $this->postJson('/api/v1/academic/enrollments/export')->assertUnauthorized();
});
