<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\FileStorage\Domain\Aggregates\StoredFile;
use Modules\FileStorage\Domain\Repositories\FileRepository;
use Modules\FileStorage\Domain\ValueObjects\StoredFileId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedFileWebUser(string $name = 'Usuario de archivos'): User
{
    $user = User::register(id: (string) Str::uuid(), name: $name, email: Email::fromString(Str::uuid().'@edudrive.cr'), passwordHash: 'hash');
    app(UserRepository::class)->save($user);

    return $user;
}

function persistedFileWebRecord(string $ownerId, string $filename): StoredFile
{
    $file = StoredFile::upload(
        id: StoredFileId::fromString((string) Str::uuid()),
        ownerId: $ownerId,
        originalFilename: $filename,
        mimeType: 'application/pdf',
        sizeBytes: 1024,
        storagePath: 'files/'.$ownerId.'/'.Str::uuid().'/'.$filename,
    );
    app(FileRepository::class)->save($file);

    return $file;
}

it('requiere autenticación para administrar archivos', function (): void {
    /** @var TestCase $this */
    $this->get('/mis-archivos')->assertRedirect(route('login'));
});

it('lista únicamente los archivos propios y oculta descarga pendiente', function (): void {
    /** @var TestCase $this */
    $user = persistedFileWebUser();
    $file = persistedFileWebRecord($user->id(), 'documento-propio.pdf');
    $other = persistedFileWebUser('Otro propietario');
    persistedFileWebRecord($other->id(), 'documento-ajeno.pdf');
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/mis-archivos')->assertOk()->assertSeeText('documento-propio.pdf')->assertDontSeeText('documento-ajeno.pdf')->assertDontSeeText('Descargar');
    $this->get('/mis-archivos/'.$file->id()->value().'/descargar')->assertRedirect()->assertSessionHas('error');
});

it('carga y elimina un archivo propio', function (): void {
    /** @var TestCase $this */
    $user = persistedFileWebUser();
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->post('/mis-archivos', ['file' => UploadedFile::fake()->create('evidencia.pdf', 50, 'application/pdf')])
        ->assertRedirect()
        ->assertSessionHas('status');
    $files = app(FileRepository::class)->allForOwner($user->id());
    expect($files)->toHaveCount(1);

    $this->delete('/mis-archivos/'.$files[0]->id()->value())->assertRedirect()->assertSessionHas('status');
    expect(app(FileRepository::class)->allForOwner($user->id()))->toBeEmpty();
});
