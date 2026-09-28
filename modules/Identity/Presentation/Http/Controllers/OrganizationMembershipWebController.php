<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Authorization\Application\Services\AccessibleOrganizationsResolver;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleAssignmentModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\OrganizationMembershipRequestModel;

final class OrganizationMembershipWebController
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['organization_id' => ['required', 'uuid', 'exists:organizations,id']]);
        $userId = (string) $request->user()?->getAuthIdentifier();
        $organizationId = (string) $data['organization_id'];

        $alreadyMember = RoleAssignmentModel::query()
            ->where('user_id', $userId)->where('role', Role::Student->value)
            ->where('organization_id', $organizationId)->exists();
        if ($alreadyMember) {
            return back()->with('organization_error', 'Ya pertenecés a esta organización como estudiante.');
        }

        $membershipRequest = OrganizationMembershipRequestModel::query()->firstOrNew([
            'user_id' => $userId,
            'organization_id' => $organizationId,
        ]);

        if (! $membershipRequest->exists) {
            $membershipRequest->id = (string) Str::uuid();
        }

        $membershipRequest->fill([
            'status' => 'pending',
            'requested_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
        ])->save();

        return back()->with('status', 'Solicitud enviada. La organización debe confirmar tu vinculación.');
    }

    public function cancel(string $membershipRequestId, Request $request): RedirectResponse
    {
        $membershipRequest = OrganizationMembershipRequestModel::query()
            ->whereKey($membershipRequestId)
            ->where('user_id', (string) $request->user()?->getAuthIdentifier())
            ->where('status', 'pending')->firstOrFail();
        $membershipRequest->delete();

        return back()->with('status', 'Solicitud cancelada.');
    }

    public function approve(
        string $membershipRequestId,
        Request $request,
        AccessibleOrganizationsResolver $accessibleOrganizations,
        RoleAssignmentRepository $roleAssignments,
    ): RedirectResponse {
        $membershipRequest = OrganizationMembershipRequestModel::query()->whereKey($membershipRequestId)->where('status', 'pending')->firstOrFail();
        $this->assertCanReview($accessibleOrganizations, (string) $request->user()?->getAuthIdentifier(), (string) $membershipRequest->organization_id);

        DB::transaction(function () use ($membershipRequest, $request, $roleAssignments): void {
            $exists = RoleAssignmentModel::query()->where('user_id', $membershipRequest->user_id)
                ->where('role', Role::Student->value)->where('organization_id', $membershipRequest->organization_id)->exists();
            if (! $exists) {
                $roleAssignments->save(RoleAssignment::assign((string) Str::uuid(), (string) $membershipRequest->user_id, Role::Student, (string) $membershipRequest->organization_id));
            }
            $membershipRequest->update(['status' => 'approved', 'reviewed_by' => (string) $request->user()?->getAuthIdentifier(), 'reviewed_at' => now()]);
        });

        return back()->with('status', 'Solicitud aprobada. El estudiante ya pertenece a la organización.');
    }

    public function reject(string $membershipRequestId, Request $request, AccessibleOrganizationsResolver $accessibleOrganizations): RedirectResponse
    {
        $membershipRequest = OrganizationMembershipRequestModel::query()->whereKey($membershipRequestId)->where('status', 'pending')->firstOrFail();
        $this->assertCanReview($accessibleOrganizations, (string) $request->user()?->getAuthIdentifier(), (string) $membershipRequest->organization_id);
        $membershipRequest->update(['status' => 'rejected', 'reviewed_by' => (string) $request->user()?->getAuthIdentifier(), 'reviewed_at' => now()]);

        return back()->with('status', 'Solicitud rechazada.');
    }

    private function assertCanReview(AccessibleOrganizationsResolver $resolver, string $userId, string $organizationId): void
    {
        $allowed = $resolver->resolveForPermission($userId, Permission::ManageUsers);
        abort_if($allowed !== null && ! in_array($organizationId, $allowed, true), 403);
    }
}
