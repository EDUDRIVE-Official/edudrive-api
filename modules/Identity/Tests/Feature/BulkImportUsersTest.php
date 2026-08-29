<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

function csvUploadFile(string $content, string $name = 'usuarios.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

it('importa usuarios en lote con el permiso users.manage, asignando student por defecto', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $email = sprintf('%s@edudrive.cr', Str::uuid());
    $csv = "name,email,password,role\n"
        ."Ana Pérez,{$email},secret123,teacher\n"
        .'Carlos Ruiz,'.sprintf('%s@edudrive.cr', Str::uuid()).",secret123,\n";

    $response = $this->post('/api/v1/users/import', ['file' => csvUploadFile($csv)], ['Accept' => 'application/json'])
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.type', 'import.users');

    $status = $this->getJson('/api/v1/async-jobs/'.$response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.result.total', 2)
        ->assertJsonPath('data.result.created', 2)
        ->assertJsonPath('data.result.failed', 0);

    $createdUserId = $status->json('data.result.results.0.user_id');
    $roles = app(RoleAssignmentRepository::class)->findByUserId((string) $createdUserId);
    expect($roles)->toHaveCount(1)->and($roles[0]->role())->toBe(Role::Teacher);

    $secondUserId = $status->json('data.result.results.1.user_id');
    $secondRoles = app(RoleAssignmentRepository::class)->findByUserId((string) $secondUserId);
    expect($secondRoles)->toHaveCount(1)->and($secondRoles[0]->role())->toBe(Role::Student);
});

it('reporta una fila fallida por correo ya existente sin detener el resto del lote', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $existing = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario existente',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($existing);

    $csv = "name,email,password,role\n"
        ."Duplicado,{$existing->email()->value()},secret123,\n"
        .'Nuevo,'.sprintf('%s@edudrive.cr', Str::uuid()).",secret123,\n";

    $response = $this->post('/api/v1/users/import', ['file' => csvUploadFile($csv)], ['Accept' => 'application/json'])
        ->assertStatus(202);

    $this->getJson('/api/v1/async-jobs/'.$response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.result.total', 2)
        ->assertJsonPath('data.result.created', 1)
        ->assertJsonPath('data.result.failed', 1)
        ->assertJsonPath('data.result.results.0.created', false)
        ->assertJsonPath('data.result.results.0.error_code', 'EMAIL_ALREADY_EXISTS')
        ->assertJsonPath('data.result.results.1.created', true);
});

it('reporta una fila fallida por rol invalido', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $csv = "name,email,password,role\n"
        .'Fila Invalida,'.sprintf('%s@edudrive.cr', Str::uuid()).",secret123,rol_inexistente\n";

    $response = $this->post('/api/v1/users/import', ['file' => csvUploadFile($csv)], ['Accept' => 'application/json'])
        ->assertStatus(202);

    $this->getJson('/api/v1/async-jobs/'.$response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.result.failed', 1)
        ->assertJsonPath('data.result.results.0.error_code', 'IMPORT_ROW_INVALID');
});

it('reporta una fila fallida por campos incompletos', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $csv = "name,email,password,role\n,,,\n";

    $response = $this->post('/api/v1/users/import', ['file' => csvUploadFile($csv)], ['Accept' => 'application/json'])
        ->assertStatus(202);

    $this->getJson('/api/v1/async-jobs/'.$response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.result.failed', 1)
        ->assertJsonPath('data.result.results.0.error_code', 'IMPORT_ROW_INVALID');
});

it('rechaza un archivo con mas de 500 filas', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $lines = ['name,email,password,role'];
    for ($i = 0; $i < 501; $i++) {
        $lines[] = "Usuario {$i},usuario{$i}@edudrive.cr,secret123,";
    }
    $csv = implode("\n", $lines)."\n";

    $this->post('/api/v1/users/import', ['file' => csvUploadFile($csv)], ['Accept' => 'application/json'])
        ->assertStatus(422);
});

it('rechaza importar usuarios sin el permiso users.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $csv = "name,email,password,role\nAna,ana@edudrive.cr,secret123,\n";

    $this->post('/api/v1/users/import', ['file' => csvUploadFile($csv)], ['Accept' => 'application/json'])
        ->assertForbidden();
});

it('requiere autenticacion para importar usuarios en lote', function (): void {
    /** @var TestCase $this */
    $csv = "name,email,password,role\nAna,ana@edudrive.cr,secret123,\n";

    $this->post('/api/v1/users/import', ['file' => csvUploadFile($csv)], ['Accept' => 'application/json'])
        ->assertUnauthorized();
});
