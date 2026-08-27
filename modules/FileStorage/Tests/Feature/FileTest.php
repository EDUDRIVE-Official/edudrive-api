<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Admin\Domain\Aggregates\SystemSetting;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;
use Modules\Authorization\Domain\Enums\Role;
use Modules\FileStorage\Domain\Aggregates\StoredFile;
use Modules\FileStorage\Domain\Repositories\FileRepository;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedFileFeatureUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Titular de archivos feature',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedFileFeature(?string $ownerId = null, int $sizeBytes = 1024): StoredFile
{
    $file = StoredFile::upload(
        id: StoredFileId::fromString((string) Str::uuid()),
        ownerId: $ownerId ?? persistedFileFeatureUserId(),
        originalFilename: 'informe.pdf',
        mimeType: 'application/pdf',
        sizeBytes: $sizeBytes,
        storagePath: sprintf('files/%s/%s/informe.pdf', $ownerId ?? 'sin-dueno', (string) Str::uuid()),
    );
    app(FileRepository::class)->save($file);

    return $file;
}

it('sube un archivo propio y lo deja en estado pending', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);

    $response = $this->post('/api/v1/files', [
        'file' => UploadedFile::fake()->create('informe.pdf', 100, 'application/pdf'),
    ], ['Accept' => 'application/json']);

    $response->assertCreated()
        ->assertJsonPath('data.owner_id', $userId)
        ->assertJsonPath('data.original_filename', 'informe.pdf')
        ->assertJsonPath('data.scan_status', 'pending');
});

it('rechaza subir un archivo que supera el limite de 20 mb por peticion', function (): void {
    /** @var TestCase $this */
    actingAsUserId((string) Str::uuid());

    $response = $this->post('/api/v1/files', [
        'file' => UploadedFile::fake()->create('grande.pdf', 21 * 1024, 'application/pdf'),
    ], ['Accept' => 'application/json']);

    $response->assertStatus(422);
});

it('rechaza subir un archivo que supera la cuota configurada del propietario', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    app(SystemSettingRepository::class)->save(
        SystemSetting::set(SystemSettingKey::fromString('file_storage_quota_bytes'), '1000'),
    );
    persistedFileFeature($userId, 900);

    $response = $this->post('/api/v1/files', [
        'file' => UploadedFile::fake()->create('informe.pdf', 1, 'application/pdf'),
    ], ['Accept' => 'application/json']);

    $response->assertStatus(422)
        ->assertJsonPath('code', 'FILE_QUOTA_EXCEEDED');
});

it('requiere autenticacion para subir un archivo', function (): void {
    /** @var TestCase $this */
    $response = $this->post('/api/v1/files', [
        'file' => UploadedFile::fake()->create('informe.pdf', 100, 'application/pdf'),
    ], ['Accept' => 'application/json']);

    $response->assertUnauthorized();
});

it('lista unicamente los archivos propios', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    persistedFileFeature($userId);
    persistedFileFeature($userId);
    persistedFileFeature();

    $this->getJson('/api/v1/files/me')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('consulta un archivo propio por id', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $file = persistedFileFeature($userId);

    $this->getJson("/api/v1/files/{$file->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $file->id()->value());
});

it('rechaza consultar el archivo de un tercero sin files.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $file = persistedFileFeature();

    $this->getJson("/api/v1/files/{$file->id()->value()}")
        ->assertNotFound()
        ->assertJsonPath('code', 'FILE_NOT_FOUND');
});

it('permite consultar el archivo de un tercero con files.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);
    $file = persistedFileFeature();

    $this->getJson("/api/v1/files/{$file->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $file->id()->value());
});

it('genera una url temporal de descarga para el propio archivo', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $file = persistedFileFeature($userId);

    $this->getJson("/api/v1/files/{$file->id()->value()}/download-url")
        ->assertOk()
        ->assertJsonStructure(['data' => ['url', 'expires_at']]);
});

it('rechaza generar una url de descarga del archivo de un tercero sin files.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $file = persistedFileFeature();

    $this->getJson("/api/v1/files/{$file->id()->value()}/download-url")
        ->assertNotFound()
        ->assertJsonPath('code', 'FILE_NOT_FOUND');
});

it('elimina un archivo propio', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $file = persistedFileFeature($userId);

    $this->deleteJson("/api/v1/files/{$file->id()->value()}")
        ->assertOk();

    $this->getJson("/api/v1/files/{$file->id()->value()}")
        ->assertNotFound();
});

it('permite eliminar el archivo de un tercero con files.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $file = persistedFileFeature();

    $this->deleteJson("/api/v1/files/{$file->id()->value()}")
        ->assertOk();
});

it('rechaza eliminar el archivo de un tercero sin files.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $file = persistedFileFeature();

    $this->deleteJson("/api/v1/files/{$file->id()->value()}")
        ->assertNotFound()
        ->assertJsonPath('code', 'FILE_NOT_FOUND');
});

it('actualiza el estado de escaneo con files.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $file = persistedFileFeature();

    $this->putJson("/api/v1/files/{$file->id()->value()}/scan-status", ['scan_status' => 'clean'])
        ->assertOk()
        ->assertJsonPath('data.scan_status', 'clean');
});

it('rechaza actualizar el estado de escaneo sin files.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $file = persistedFileFeature();

    $this->putJson("/api/v1/files/{$file->id()->value()}/scan-status", ['scan_status' => 'clean'])
        ->assertForbidden();
});

it('requiere autenticacion para los endpoints protegidos de archivos', function (): void {
    /** @var TestCase $this */
    $file = persistedFileFeature();

    $this->getJson('/api/v1/files/me')->assertUnauthorized();
    $this->getJson("/api/v1/files/{$file->id()->value()}")->assertUnauthorized();
    $this->getJson("/api/v1/files/{$file->id()->value()}/download-url")->assertUnauthorized();
    $this->deleteJson("/api/v1/files/{$file->id()->value()}")->assertUnauthorized();
    $this->putJson("/api/v1/files/{$file->id()->value()}/scan-status", ['scan_status' => 'clean'])->assertUnauthorized();
});
