<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiModel;
use Modules\AiGovernance\Domain\Repositories\AiModelRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiModelId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedAiGovernanceWebUser(): UserModel
{
    $user = User::register(id: (string) Str::uuid(), name: 'Usuario sin gobierno IA', email: Email::fromString(Str::uuid().'@edudrive.cr'), passwordHash: 'hash');
    app(UserRepository::class)->save($user);

    return UserModel::query()->findOrFail($user->id());
}

it('requiere autenticación y permiso para abrir gobierno de IA', function (): void {
    /** @var TestCase $this */
    $this->get('/admin/gobierno-ia')->assertRedirect(route('login'));
    $this->actingAs(persistedAiGovernanceWebUser(), 'web');
    $this->get('/admin/gobierno-ia')->assertForbidden();
});

it('muestra el inventario y sus indicadores sin habilitar invocaciones', function (): void {
    /** @var TestCase $this */
    $model = AiModel::register(
        id: AiModelId::fromString((string) Str::uuid()),
        name: 'Modelo tutor vial',
        provider: 'Proveedor controlado',
        version: '1.0',
        ownerId: null,
        useCase: 'Apoyo educativo',
        knownRisks: 'Alucinaciones',
    );
    app(AiModelRepository::class)->save($model);
    $this->actingAs(actingAsSuperAdminUser(), 'web');

    $this->get('/admin/gobierno-ia')
        ->assertOk()
        ->assertSeeText('Gobierno de IA')
        ->assertSeeText('Modelo tutor vial')
        ->assertSeeText('Proveedor controlado')
        ->assertDontSee('gateway/invoke');
});
