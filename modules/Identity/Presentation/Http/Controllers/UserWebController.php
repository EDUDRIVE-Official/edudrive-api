<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\EnrollmentModel;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Authorization\Application\Services\AccessibleOrganizationsResolver;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleAssignmentModel;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Modules\Identity\Application\Commands\ActivateUserCommand;
use Modules\Identity\Application\Commands\CreateGuardianRelationshipCommand;
use Modules\Identity\Application\Commands\DeactivateUserCommand;
use Modules\Identity\Application\Commands\RevokeGuardianRelationshipCommand;
use Modules\Identity\Application\Commands\SendEmailVerificationCommand;
use Modules\Identity\Application\Responses\UserResponse;
use Modules\Identity\Application\Services\AccessTokenRevoker;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Application\Services\UserAdministrationScope;
use Modules\Identity\Application\Services\StudentLearningResetService;
use Modules\Identity\Application\UseCases\ActivateUserUseCase;
use Modules\Identity\Application\UseCases\CreateGuardianRelationshipHandler;
use Modules\Identity\Application\UseCases\DeactivateUserUseCase;
use Modules\Identity\Application\UseCases\ListUsersUseCase;
use Modules\Identity\Application\UseCases\RevokeGuardianRelationshipHandler;
use Modules\Identity\Application\UseCases\SendEmailVerificationUseCase;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\GuardianRelationshipModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\StudentLearningResetModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Identity\Presentation\Http\Requests\CreateGuardianRelationshipRequest;
use Modules\Identity\Presentation\Http\Requests\ResetStudentLearningRequest;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Models\OrganizationModel;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;

final class UserWebController extends Controller
{
    public function __construct(
        private readonly ListUsersUseCase $listUsers,
        private readonly ActivateUserUseCase $activateUser,
        private readonly DeactivateUserUseCase $deactivateUser,
        private readonly CreateGuardianRelationshipHandler $createGuardianRelationship,
        private readonly RevokeGuardianRelationshipHandler $revokeGuardianRelationship,
        private readonly UserRepository $userRepository,
        private readonly RoleAssignmentRepository $roleAssignments,
        private readonly AccessibleOrganizationsResolver $accessibleOrganizations,
        private readonly PasswordHasher $passwordHasher,
        private readonly AccessTokenRevoker $tokenRevoker,
        private readonly AuditLogger $audit,
        private readonly RoadPassportRepository $passports,
        private readonly SendEmailVerificationUseCase $emailVerification,
        private readonly UserAdministrationScope $userScope,
        private readonly StudentLearningResetService $learningReset,
    ) {}

    public function index(Request $request, PermissionChecker $checker): View
    {
        $users = array_map(
            static fn (UserResponse $user): array => $user->toArray(),
            $this->listUsers->execute(),
        );
        $users = array_values(array_filter($users, fn (array $user): bool => $this->canAccessUser((string) auth()->id(), $user['id'], Permission::ViewUsers)));
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        if ($search !== '') {
            $users = array_values(array_filter($users, static fn (array $user): bool => str_contains(mb_strtolower($user['name'].' '.$user['email']), mb_strtolower($search))));
        }
        if ($status !== '') {
            $users = array_values(array_filter($users, static fn (array $user): bool => $user['status'] === $status));
        }
        $assignmentsByUser = RoleAssignmentModel::query()->whereIn('user_id', array_column($users, 'id'))->get()->groupBy('user_id');
        $users = array_map(static function (array $user) use ($assignmentsByUser): array {
            $user['roles'] = $assignmentsByUser->get($user['id'], collect())->pluck('role')->unique()->values()->all();

            return $user;
        }, $users);
        $names = collect($users)->pluck('name', 'id');
        $visibleUserIds = array_column($users, 'id');
        $relationships = GuardianRelationshipModel::query()->whereNull('revoked_at')
            ->whereIn('guardian_user_id', $visibleUserIds)
            ->whereIn('minor_user_id', $visibleUserIds)
            ->orderBy('created_at')->get()
            ->map(static fn (GuardianRelationshipModel $relationship): array => [
                'id' => $relationship->id,
                'guardian_name' => $names->get($relationship->guardian_user_id, 'Persona adulta'),
                'minor_name' => $names->get($relationship->minor_user_id, 'Persona menor'),
            ])->all();

        return view('users.index', [
            'users' => $users,
            'relationships' => $relationships,
            'canManage' => $checker->userHasPermission(
                (string) auth()->id(),
                Permission::ManageUsers,
            ),
            'canManageGuardians' => $checker->userHasPermission((string) auth()->id(), Permission::ManageGuardianRelationships),
            'search' => $search,
            'statusFilter' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        return view('users.form', [
            'managedUser' => null,
            'organizations' => $this->accessibleOrganizationOptions((string) $request->user()?->getAuthIdentifier()),
            'assignableRoles' => $this->assignableRoles((string) $request->user()?->getAuthIdentifier()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actorId = (string) $request->user()?->getAuthIdentifier();
        $data = $this->validateUserData($request, null, true);
        $this->assertOrganizationAllowed($actorId, $data['organization_id'] ?? null);
        $role = Role::from($data['role']);
        $this->assertRoleAllowed($actorId, $role);
        $user = User::register(
            id: (string) Str::uuid(),
            name: $data['name'],
            email: Email::fromString($data['email']),
            passwordHash: $this->passwordHasher->hash($data['password']),
            dateOfBirth: isset($data['date_of_birth']) ? new DateTimeImmutable($data['date_of_birth']) : null,
        );
        if ((bool) ($data['activate_now'] ?? false)) {
            $user->activate(new DateTimeImmutable('now'));
        }
        $this->userRepository->save($user);
        $this->roleAssignments->save(RoleAssignment::assign((string) Str::uuid(), $user->id(), $role, $data['organization_id'] ?? null));
        $this->log('identity.admin_user_created', $actorId, $user->id(), ['role' => $role->value, 'organization_id' => $data['organization_id'] ?? null]);
        if ($user->emailVerifiedAt() === null) {
            $this->emailVerification->execute(new SendEmailVerificationCommand($user->email()->value()));
        }

        return redirect()->route('users.show', $user->id())->with('status', 'Usuario creado correctamente.');
    }

    public function show(Request $request, string $userId, PermissionChecker $checker): View
    {
        $actorId = (string) $request->user()?->getAuthIdentifier();
        $this->authorizeUserAccess($actorId, $userId, Permission::ViewUsers);
        $user = $this->userRepository->findById($userId);
        abort_if($user === null, 404);
        $organizations = OrganizationModel::query()->pluck('name', 'id');
        $roles = array_map(static fn ($assignment): array => [
            'id' => $assignment->id(),
            'role' => $assignment->role()->value,
            'organization_id' => $assignment->organizationId(),
            'organization_name' => $assignment->organizationId() === null ? 'Global' : ($organizations[$assignment->organizationId()] ?? 'Organización'),
        ], $this->roleAssignments->findByUserId($userId));
        $audit = AuditLogModel::query()->where('entity_id', $userId)->latest('occurred_at')->limit(20)->get();
        $learningResets = StudentLearningResetModel::query()->where('user_id', $userId)->latest('reset_at')->get();
        $resetActors = UserModel::query()->whereIn('id', $learningResets->pluck('performed_by_user_id')->filter())->pluck('name', 'id');
        $isStudent = RoleAssignmentModel::query()->where('user_id', $userId)->where('role', Role::Student->value)->exists();

        return view('users.show', [
            'managedUser' => UserResponse::fromUser($user)->toArray(),
            'roles' => $roles,
            'auditEntries' => $audit,
            'enrollmentCount' => EnrollmentModel::query()->where('user_id', $userId)->count(),
            'hasPassport' => $this->passports->findByUserId($userId) !== null,
            'activeSessionCount' => DB::table('sessions')->where('user_id', $userId)->count() + PersonalAccessToken::query()->where('tokenable_id', $userId)->count(),
            'canManage' => $checker->userHasPermission($actorId, Permission::ManageUsers),
            'canManageRoles' => $checker->userHasPermission($actorId, Permission::ManageRoleAssignments),
            'organizations' => $this->accessibleOrganizationOptions($actorId),
            'assignableRoles' => $this->assignableRoles($actorId),
            'isSelf' => $actorId === $userId,
            'canResetLearning' => $actorId !== $userId && $isStudent && $checker->userHasPermission($actorId, Permission::ManageRoleAssignments),
            'learningResets' => $learningResets,
            'resetActors' => $resetActors,
        ]);
    }

    public function resetLearning(ResetStudentLearningRequest $request, string $userId): RedirectResponse
    {
        $actorId = (string) $request->user()?->getAuthIdentifier();
        abort_if($actorId === $userId, 422, 'No podés reiniciar tu propio expediente educativo.');
        abort_unless(RoleAssignmentModel::query()->where('user_id', $userId)->where('role', Role::Student->value)->exists(), 422, 'El usuario seleccionado no es estudiante.');
        abort_if($this->userRepository->findById($userId) === null, 404);

        $result = $this->learningReset->reset($userId, $actorId, (string) $request->validated('reason'));
        $this->log('identity.student_learning_reset', $actorId, $userId, [
            'reset_id' => $result['reset']->id,
            'summary' => $result['summary'],
        ]);

        return redirect()->route('users.show', $userId)
            ->with('status', 'El expediente anterior fue archivado y el estudiante puede comenzar desde cero.');
    }

    public function edit(Request $request, string $userId): View
    {
        $actorId = (string) $request->user()?->getAuthIdentifier();
        $this->authorizeUserAccess($actorId, $userId, Permission::ManageUsers);
        $user = $this->userRepository->findById($userId);
        abort_if($user === null, 404);

        return view('users.form', [
            'managedUser' => UserResponse::fromUser($user)->toArray(),
            'organizations' => [],
            'assignableRoles' => [],
        ]);
    }

    public function update(Request $request, string $userId): RedirectResponse
    {
        $actorId = (string) $request->user()?->getAuthIdentifier();
        $this->authorizeUserAccess($actorId, $userId, Permission::ManageUsers);
        $user = $this->userRepository->findById($userId);
        abort_if($user === null, 404);
        $data = $this->validateUserData($request, $userId, false);
        $now = new DateTimeImmutable('now');
        $previousEmail = $user->email()->value();
        $user->rename($data['name'], $now);
        $user->changeEmail(Email::fromString($data['email']), $now);
        $user->changeDateOfBirth(isset($data['date_of_birth']) ? new DateTimeImmutable($data['date_of_birth']) : null, $now);
        $this->userRepository->save($user);
        $this->log('identity.admin_user_updated', $actorId, $userId);
        if ($previousEmail !== $user->email()->value()) {
            $this->emailVerification->execute(new SendEmailVerificationCommand($user->email()->value()));
        }

        return redirect()->route('users.show', $userId)->with('status', 'Datos del usuario actualizados.');
    }

    public function linkGuardian(CreateGuardianRelationshipRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $actorId = (string) $request->user()?->getAuthIdentifier();
        $this->authorizeUserAccess($actorId, (string) $data['guardian_user_id'], Permission::ManageGuardianRelationships);
        $this->authorizeUserAccess($actorId, (string) $data['minor_user_id'], Permission::ManageGuardianRelationships);
        try {
            $this->createGuardianRelationship->handle(new CreateGuardianRelationshipCommand(
                guardianUserId: (string) $data['guardian_user_id'],
                minorUserId: (string) $data['minor_user_id'],
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ));
        } catch (DomainException $exception) {
            return redirect()->route('users.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('users.index')->with('status', 'Acompañamiento familiar vinculado correctamente.');
    }

    public function revokeGuardian(Request $request, string $relationshipId): RedirectResponse
    {
        $relationship = GuardianRelationshipModel::query()->findOrFail($relationshipId);
        $actorId = (string) $request->user()?->getAuthIdentifier();
        $this->authorizeUserAccess($actorId, (string) $relationship->guardian_user_id, Permission::ManageGuardianRelationships);
        $this->authorizeUserAccess($actorId, (string) $relationship->minor_user_id, Permission::ManageGuardianRelationships);
        try {
            $this->revokeGuardianRelationship->handle(new RevokeGuardianRelationshipCommand(
                relationshipId: $relationshipId,
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ));
        } catch (DomainException $exception) {
            return redirect()->route('users.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('users.index')->with('status', 'Relación de acompañamiento retirada.');
    }

    public function activate(Request $request, string $userId): RedirectResponse
    {
        $this->authorizeUserAccess((string) $request->user()?->getAuthIdentifier(), $userId, Permission::ManageUsers);
        $response = $this->activateUser->execute(
            new ActivateUserCommand(
                userId: $userId,
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ),
        );

        return redirect()
            ->route('users.index')
            ->with('status', $response->message);
    }

    public function deactivate(Request $request, string $userId): RedirectResponse
    {
        abort_if((string) $request->user()?->getAuthIdentifier() === $userId, 422, 'No podés desactivar tu propia cuenta administrativa.');
        $this->authorizeUserAccess((string) $request->user()?->getAuthIdentifier(), $userId, Permission::ManageUsers);
        $response = $this->deactivateUser->execute(
            new DeactivateUserCommand(
                userId: $userId,
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ),
        );

        return redirect()
            ->route('users.index')
            ->with('status', $response->message);
    }

    public function resetTemporaryPassword(Request $request, string $userId): RedirectResponse
    {
        $actorId = (string) $request->user()?->getAuthIdentifier();
        $this->authorizeUserAccess($actorId, $userId, Permission::ManageUsers);
        $user = $this->userRepository->findById($userId);
        abort_if($user === null, 404);
        $temporaryPassword = Str::password(16, symbols: false);
        $user->changePasswordHash($this->passwordHasher->hash($temporaryPassword), new DateTimeImmutable('now'));
        $this->userRepository->save($user);
        $this->tokenRevoker->revokeAllForUser($userId);
        DB::table('sessions')->where('user_id', $userId)->delete();
        $this->log('identity.admin_temporary_password_issued', $actorId, $userId);

        return redirect()->route('users.show', $userId)
            ->with('status', 'Contraseña temporal generada. Se cerraron las sesiones activas.')
            ->with('temporary_password', $temporaryPassword);
    }

    public function anonymize(Request $request, string $userId): RedirectResponse
    {
        $actorId = (string) $request->user()?->getAuthIdentifier();
        abort_if($actorId === $userId, 422, 'No podés anonimizar tu propia cuenta administrativa.');
        $this->authorizeUserAccess($actorId, $userId, Permission::ManageUsers);
        $user = $this->userRepository->findById($userId);
        abort_if($user === null, 404);
        $now = new DateTimeImmutable('now');
        $anonymousKey = str_replace('-', '', $userId);
        $user->rename('Usuario anonimizado', $now);
        $user->changeEmail(Email::fromString('anon-'.$anonymousKey.'@deleted.edudrive.invalid'), $now);
        $user->changeDateOfBirth(null, $now);
        $user->changePasswordHash($this->passwordHasher->hash(Str::random(64)), $now);
        $user->deactivate($now);
        $this->userRepository->save($user);
        $this->tokenRevoker->revokeAllForUser($userId);
        DB::table('sessions')->where('user_id', $userId)->delete();
        RoleAssignmentModel::query()->where('user_id', $userId)->delete();
        GuardianRelationshipModel::query()->whereNull('revoked_at')
            ->where(fn ($query) => $query->where('guardian_user_id', $userId)->orWhere('minor_user_id', $userId))
            ->update(['revoked_at' => $now]);
        $this->log('identity.account_anonymized', $actorId, $userId);

        return redirect()->route('users.index')->with('status', 'Cuenta anonimizada. El historial educativo se conservó sin datos personales.');
    }

    public function revokeRole(Request $request, string $userId, string $assignmentId): RedirectResponse
    {
        $actorId = (string) $request->user()?->getAuthIdentifier();
        abort_if($actorId === $userId, 422, 'No podés retirar tus propios permisos administrativos.');
        $assignment = RoleAssignmentModel::query()->whereKey($assignmentId)->where('user_id', $userId)->firstOrFail();
        $this->authorizeUserAccess($actorId, $userId, Permission::ManageRoleAssignments);
        $assignment->delete();
        $this->log('authorization.role_revoked', $actorId, $assignmentId, ['target_user_id' => $userId, 'role' => $assignment->role]);

        return redirect()->route('users.show', $userId)->with('status', 'Rol retirado correctamente.');
    }

    /** @return array<string, mixed> */
    private function validateUserData(Request $request, ?string $userId, bool $creating): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:12', 'confirmed'],
            'role' => [$creating ? 'required' : 'nullable', Rule::enum(Role::class)],
            'organization_id' => [
                Rule::requiredIf(fn (): bool => $request->input('role') === Role::InstitutionalAdmin->value),
                'nullable',
                'uuid',
                'exists:organizations,id',
            ],
            'activate_now' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeUserAccess(string $actorId, string $targetUserId, Permission $permission): void
    {
        abort_unless($this->canAccessUser($actorId, $targetUserId, $permission), 404);
    }

    private function canAccessUser(string $actorId, string $targetUserId, Permission $permission): bool
    {
        return $this->userScope->canAccess($actorId, $targetUserId, $permission);
    }

    /** @return array<string, string> */
    private function accessibleOrganizationOptions(string $actorId): array
    {
        $organizationIds = $this->accessibleOrganizations->resolveForPermission($actorId, Permission::ManageUsers);
        $query = OrganizationModel::query()->orderBy('name');
        if ($organizationIds !== null) {
            $query->whereIn('id', $organizationIds);
        }

        return $query->pluck('name', 'id')->all();
    }

    /** @return list<Role> */
    private function assignableRoles(string $actorId): array
    {
        $global = $this->accessibleOrganizations->resolveForPermission($actorId, Permission::ManageRoleAssignments) === null;

        return $global ? Role::cases() : [Role::Teacher, Role::Student];
    }

    private function assertOrganizationAllowed(string $actorId, ?string $organizationId): void
    {
        $allowed = $this->accessibleOrganizations->resolveForPermission($actorId, Permission::ManageUsers);
        abort_if($allowed !== null && ($organizationId === null || ! in_array($organizationId, $allowed, true)), 403);
    }

    private function assertRoleAllowed(string $actorId, Role $role): void
    {
        abort_if(! in_array($role, $this->assignableRoles($actorId), true), 403);
    }

    /** @param array<string, mixed> $metadata */
    private function log(string $action, string $actorId, string $entityId, array $metadata = []): void
    {
        $this->audit->log(new AuditEntry($action, $actorId, 'User', $entityId, metadata: $metadata));
    }
}
