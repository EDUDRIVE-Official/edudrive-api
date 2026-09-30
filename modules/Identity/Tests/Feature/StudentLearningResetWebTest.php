<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\StudentLearningResetModel;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Models\OrganizationModel;

uses(RefreshDatabase::class);

function learningResetStudent(): string
{
    $id = (string) Str::uuid();
    app(UserRepository::class)->save(User::register(
        id: $id,
        name: 'Estudiante para reinicio',
        email: Email::fromString("{$id}@edudrive.cr"),
        passwordHash: 'hashed-password',
    ));
    app(RoleAssignmentRepository::class)->save(RoleAssignment::assign(
        id: (string) Str::uuid(), userId: $id, role: Role::Student, organizationId: null,
    ));

    return $id;
}

it('archiva cifrado el expediente y entrega un pasaporte nuevo y limpio', function (): void {
    $admin = actingAsSuperAdminUser();
    $studentId = learningResetStudent();
    $oldPassportId = (string) Str::uuid();
    DB::table('road_passports')->insert([
        'id' => $oldPassportId, 'user_id' => $studentId, 'status' => 'active', 'level' => 4,
        'issued_at' => now(), 'verification_version' => 3, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->post(route('users.learning.reset', $studentId), [
        'reason' => 'El estudiante iniciará un nuevo proceso educativo.',
        'confirmation' => 'REINICIAR',
    ])->assertRedirect(route('users.show', $studentId));

    $newPassport = DB::table('road_passports')->where('user_id', $studentId)->first();
    $reset = StudentLearningResetModel::query()->where('user_id', $studentId)->firstOrFail();

    expect($newPassport->id)->not->toBe($oldPassportId)
        ->and($newPassport->level)->toBe(1)
        ->and($reset->performed_by_user_id)->toBe($admin->id)
        ->and($reset->encrypted_snapshot['records']['road_passports'][0]['id'])->toBe($oldPassportId);
    $this->assertDatabaseHas('users', ['id' => $studentId]);
    $this->assertDatabaseHas('authorization_role_assignments', ['user_id' => $studentId, 'role' => Role::Student->value]);
    expect(DB::table('identity_student_learning_resets')->value('encrypted_snapshot'))->not->toContain($oldPassportId);
    $this->get(route('users.show', $studentId))
        ->assertOk()
        ->assertSee('Reinicios del aprendizaje')
        ->assertSee('Pasaporte vial anterior: 1')
        ->assertSee('El estudiante iniciará un nuevo proceso educativo.');
});

it('exige confirmacion exacta antes de reiniciar', function (): void {
    actingAsSuperAdminUser();
    $studentId = learningResetStudent();

    $this->from(route('users.show', $studentId))->post(route('users.learning.reset', $studentId), [
        'reason' => 'Solicitud válida con motivo suficiente.',
        'confirmation' => 'reiniciar',
    ])->assertRedirect(route('users.show', $studentId))->assertSessionHasErrors('confirmation');

    $this->assertDatabaseMissing('identity_student_learning_resets', ['user_id' => $studentId]);
});

it('impide que un administrador institucional reinicie el expediente', function (): void {
    $organizationId = (string) Str::uuid();
    OrganizationModel::query()->create([
        'id' => $organizationId,
        'name' => 'Centro educativo de prueba',
        'type' => 'educational_center',
    ]);
    $admin = actingAsRole(Role::InstitutionalAdmin);
    DB::table('authorization_role_assignments')->where('user_id', $admin->id)->update(['organization_id' => $organizationId]);
    $studentId = learningResetStudent();
    DB::table('authorization_role_assignments')->where('user_id', $studentId)->update(['organization_id' => $organizationId]);

    $this->post(route('users.learning.reset', $studentId), [
        'reason' => 'Intento de reinicio desde la organización.',
        'confirmation' => 'REINICIAR',
    ])->assertForbidden();

    $this->assertDatabaseMissing('identity_student_learning_resets', ['user_id' => $studentId]);
});
