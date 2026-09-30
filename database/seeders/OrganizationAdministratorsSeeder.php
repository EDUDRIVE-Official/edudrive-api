<?php

declare(strict_types=1);

namespace Database\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleAssignmentModel;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Models\OrganizationModel;

final class OrganizationAdministratorsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $userRepository = app(UserRepository::class);
        $roleAssignments = app(RoleAssignmentRepository::class);
        $passwordHash = app(PasswordHasher::class)->hash('AdminEduDrive2026!');

        OrganizationModel::query()->orderBy('name')->get()->each(
            function (OrganizationModel $organization) use ($userRepository, $roleAssignments, $passwordHash): void {
                DB::transaction(function () use ($organization, $userRepository, $roleAssignments, $passwordHash): void {
                    $email = 'admin.'.Str::slug((string) $organization->name, '.').'@edudrive.test';
                    $userModel = UserModel::query()->where('email', $email)->first();

                    if ($userModel === null) {
                        $user = User::register(
                            id: (string) Str::uuid(),
                            name: 'Administración '.(string) $organization->name,
                            email: Email::fromString($email),
                            passwordHash: $passwordHash,
                        );
                        $user->activate(new DateTimeImmutable('now'));
                        $userRepository->save($user);
                        $userId = $user->id();
                    } else {
                        $userId = (string) $userModel->id;
                    }

                    $hasAssignment = RoleAssignmentModel::query()
                        ->where('user_id', $userId)
                        ->where('role', Role::InstitutionalAdmin->value)
                        ->where('organization_id', (string) $organization->id)
                        ->exists();

                    if (! $hasAssignment) {
                        $roleAssignments->save(RoleAssignment::assign(
                            id: (string) Str::uuid(),
                            userId: $userId,
                            role: Role::InstitutionalAdmin,
                            organizationId: (string) $organization->id,
                        ));
                    }
                });
            },
        );
    }
}
