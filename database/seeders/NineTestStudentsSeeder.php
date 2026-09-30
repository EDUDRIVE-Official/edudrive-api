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

final class NineTestStudentsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $users = [
            ['name' => 'Sofía Rodríguez', 'email' => 'estudiante04@edudrive.test', 'date_of_birth' => '2017-03-12'],
            ['name' => 'Mateo Vargas', 'email' => 'estudiante05@edudrive.test', 'date_of_birth' => '2015-07-24'],
            ['name' => 'Valentina Jiménez', 'email' => 'estudiante06@edudrive.test', 'date_of_birth' => '2012-11-08'],
            ['name' => 'Santiago Mora', 'email' => 'estudiante07@edudrive.test', 'date_of_birth' => '2009-05-17'],
            ['name' => 'Isabella Hernández', 'email' => 'estudiante08@edudrive.test', 'date_of_birth' => '2007-09-30'],
            ['name' => 'Daniel Chaves', 'email' => 'estudiante09@edudrive.test', 'date_of_birth' => '2004-02-14'],
            ['name' => 'Camila Solano', 'email' => 'estudiante10@edudrive.test', 'date_of_birth' => '1998-06-21'],
            ['name' => 'Sebastián Araya', 'email' => 'estudiante11@edudrive.test', 'date_of_birth' => '1986-12-03'],
            ['name' => 'Mariana Quesada', 'email' => 'estudiante12@edudrive.test', 'date_of_birth' => '1974-04-26'],
        ];

        $userRepository = app(UserRepository::class);
        $roleAssignments = app(RoleAssignmentRepository::class);
        $passwordHash = app(PasswordHasher::class)->hash('EduDrive2026!');

        foreach ($users as $data) {
            DB::transaction(function () use ($data, $userRepository, $roleAssignments, $passwordHash): void {
                $userModel = UserModel::query()->where('email', $data['email'])->first();

                if ($userModel === null) {
                    $user = User::register(
                        id: (string) Str::uuid(),
                        name: $data['name'],
                        email: Email::fromString($data['email']),
                        passwordHash: $passwordHash,
                        dateOfBirth: new DateTimeImmutable($data['date_of_birth']),
                    );
                    $user->activate(new DateTimeImmutable('now'));
                    $userRepository->save($user);
                    $userId = $user->id();
                } else {
                    $userId = (string) $userModel->id;
                }

                $hasStudentRole = RoleAssignmentModel::query()
                    ->where('user_id', $userId)
                    ->where('role', Role::Student->value)
                    ->whereNull('organization_id')
                    ->exists();

                if (! $hasStudentRole) {
                    $roleAssignments->save(RoleAssignment::assign(
                        id: (string) Str::uuid(),
                        userId: $userId,
                        role: Role::Student,
                        organizationId: null,
                    ));
                }
            });
        }
    }
}
