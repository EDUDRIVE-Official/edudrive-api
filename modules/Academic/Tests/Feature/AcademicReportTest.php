<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

it('consulta los cinco reportes academicos con el permiso reports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->getJson('/api/v1/academic/reports/progress')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/academic/reports/performance')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/academic/reports/approval')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/academic/reports/competencies')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/academic/reports/activity')->assertOk()->assertJsonStructure(['data']);
});

it('permite consultar los reportes al administrador institucional', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->getJson('/api/v1/academic/reports/progress')->assertOk();
});

it('rechaza consultar los reportes academicos sin el permiso reports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/reports/progress')->assertForbidden();
    $this->getJson('/api/v1/academic/reports/performance')->assertForbidden();
    $this->getJson('/api/v1/academic/reports/approval')->assertForbidden();
    $this->getJson('/api/v1/academic/reports/competencies')->assertForbidden();
    $this->getJson('/api/v1/academic/reports/activity')->assertForbidden();
});

it('requiere autenticacion para consultar los reportes academicos', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/academic/reports/progress')->assertUnauthorized();
    $this->getJson('/api/v1/academic/reports/performance')->assertUnauthorized();
    $this->getJson('/api/v1/academic/reports/approval')->assertUnauthorized();
    $this->getJson('/api/v1/academic/reports/competencies')->assertUnauthorized();
    $this->getJson('/api/v1/academic/reports/activity')->assertUnauthorized();
});

it('filtra por course_ids cuando se especifican', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $course = createDraftCourseForPublishing('FLT-'.strtoupper((string) Str::random(4)));

    $this->getJson("/api/v1/academic/reports/progress?course_ids[]={$course->id()->value()}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.course_id', $course->id()->value());
});
