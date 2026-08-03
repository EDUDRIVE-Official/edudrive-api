<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

uses(RefreshDatabase::class);

function actingAsAcademicRole(Role $role): void
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario académico',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);
    app(RoleAssignmentRepository::class)->save(RoleAssignment::assign(
        id: (string) Str::uuid(),
        userId: $user->id(),
        role: $role,
        organizationId: null,
    ));
    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));
}

it('crea y consulta una competencia con su jerarquía', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $created = $this->postJson('/api/v1/academic/competencies', [
        'code' => 'risk-100',
        'title' => 'Anticipación de riesgos',
        'description' => 'Reconoce riesgos antes de que se materialicen.',
        'category' => 'risk_management',
        'mastery_level' => 'foundation',
    ])->assertCreated()
        ->assertJsonPath('data.code', 'RISK-100')
        ->assertJsonPath('data.status', 'active');

    $competencyId = (string) $created->json('data.id');

    $this->postJson("/api/v1/academic/competencies/{$competencyId}/subcompetencies", [
        'code' => 'risk-100.01',
        'title' => 'Observación del entorno',
    ])->assertOk()
        ->assertJsonPath('data.subcompetencies.0.code', 'RISK-100.01');

    $this->postJson("/api/v1/academic/competencies/{$competencyId}/subcompetencies/RISK-100.01/indicators", [
        'code' => 'risk-100.01.i01',
        'description' => 'Identifica peligros visibles con anticipación.',
    ])->assertOk()
        ->assertJsonPath('data.subcompetencies.0.indicators.0.code', 'RISK-100.01.I01');

    $this->getJson('/api/v1/academic/competencies')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.category', 'risk_management')
        ->assertJsonPath('data.0.mastery_level', 'foundation')
        ->assertJsonPath('data.0.subcompetencies.0.indicators.0.position', 1);
});

it('valida categoría y nivel de dominio', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $this->postJson('/api/v1/academic/competencies', [
        'code' => 'RISK-101',
        'title' => 'Competencia inválida',
        'description' => 'Datos con vocabularios inválidos.',
        'category' => 'unknown',
        'mastery_level' => 'expert',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['category', 'mastery_level']);
});

it('rechaza códigos duplicados con conflicto', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $payload = [
        'code' => 'RISK-102',
        'title' => 'Competencia regional',
        'description' => 'Capacidad compartida en Latinoamérica.',
        'category' => 'risk_management',
        'mastery_level' => 'developing',
    ];
    $this->postJson('/api/v1/academic/competencies', $payload)->assertCreated();
    $this->postJson('/api/v1/academic/competencies', $payload)
        ->assertConflict()
        ->assertJsonPath('code', 'COMPETENCY_CODE_ALREADY_EXISTS');
});

it('permite consultar con competencies.view pero rechaza cambios sin competencies.manage', function (): void {
    /** @var TestCase $this */
    actingAsAcademicRole(Role::Teacher);

    $this->getJson('/api/v1/academic/competencies')->assertOk();
    $this->postJson('/api/v1/academic/competencies', [
        'code' => 'RISK-103',
        'title' => 'Sin permiso',
        'description' => 'No debe crearse.',
        'category' => 'risk_management',
        'mastery_level' => 'foundation',
    ])->assertForbidden();
});

it('protege el catálogo con autenticación', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/academic/competencies')->assertUnauthorized();
});
