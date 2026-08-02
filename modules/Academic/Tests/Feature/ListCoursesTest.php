<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseModel;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('lists academic courses', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    CourseModel::query()->create([
        'id' => '3fdab59d-7431-440f-bdab-f55798e99a79',
        'code' => 'SV-001',
        'title' => 'Seguridad Vial',
        'description' => 'Curso introductorio de seguridad vial.',
        'status' => 'draft',
        'published_at' => null,
        'archived_at' => null,
    ]);

    CourseModel::query()->create([
        'id' => '0844b7fa-5d71-41c6-a59d-864cf7927cc3',
        'code' => 'MOT-001',
        'title' => 'Conducción de Motocicletas',
        'description' => null,
        'status' => 'draft',
        'published_at' => null,
        'archived_at' => null,
    ]);

    $response = $this->getJson('/api/v1/academic/courses');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.code', 'SV-001')
        ->assertJsonPath('data.0.title', 'Seguridad Vial')
        ->assertJsonPath('data.0.status', 'draft')
        ->assertJsonPath('data.1.code', 'MOT-001')
        ->assertJsonPath('data.1.title', 'Conducción de Motocicletas')
        ->assertJsonPath('data.1.status', 'draft');
});

it('rechaza el listado sin autenticación', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/academic/courses')->assertUnauthorized();
});

it('permite listar a un usuario con solo el permiso courses.view', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Docente',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Teacher,
            organizationId: null,
        ),
    );

    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));

    $this->getJson('/api/v1/academic/courses')->assertOk();
});
