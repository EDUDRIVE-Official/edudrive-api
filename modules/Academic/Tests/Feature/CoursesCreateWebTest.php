<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use function Pest\Laravel\assertDatabaseHas;

use Tests\TestCase;

it('rechaza a un usuario con solo permiso de vista', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Docente Web',
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

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/courses/create')->assertForbidden();
    $this->post('/courses', ['code' => 'EDU-200', 'title' => 'Curso X'])->assertForbidden();
});

it('muestra el formulario de creación a un superadministrador', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $this->get('/courses/create')
        ->assertOk()
        ->assertSeeText('Nuevo curso');
});

it('crea un curso con todos los campos y redirige a la lista con un mensaje de éxito', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $response = $this->post('/courses', [
        'code' => 'EDU-201',
        'title' => 'Manejo Defensivo Web',
        'description' => 'Curso completo de manejo defensivo.',
        'objectives' => 'Aplicar técnicas de manejo defensivo.',
        'prerequisites' => 'Licencia vigente.',
        'modality' => 'hybrid',
        'duration_hours' => 18,
    ]);

    $response->assertRedirect(route('courses.index'));
    $response->assertSessionHas('status');

    assertDatabaseHas('academic_courses', [
        'code' => 'EDU-201',
        'title' => 'Manejo Defensivo Web',
        'modality' => 'hybrid',
        'duration_hours' => 18,
    ]);
});

it('vuelve al formulario con errores cuando faltan datos obligatorios', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $response = $this->post('/courses', []);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['code', 'title']);
});

it('vuelve al formulario con error cuando la modalidad es inválida', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $response = $this->post('/courses', [
        'code' => 'EDU-202',
        'title' => 'Curso con modalidad inválida',
        'modality' => 'no-existe',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['modality']);
});
