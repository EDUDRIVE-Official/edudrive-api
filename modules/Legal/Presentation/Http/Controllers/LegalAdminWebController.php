<?php

declare(strict_types=1);

namespace Modules\Legal\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Modules\Legal\Application\Commands\PublishPolicyVersionCommand;
use Modules\Legal\Application\Queries\GetOrganizationMinorsConsentsQuery;
use Modules\Legal\Application\Queries\ListPoliciesQuery;
use Modules\Legal\Application\Responses\OrganizationMinorConsentsResponse;
use Modules\Legal\Application\Responses\PolicyResponse;
use Modules\Legal\Presentation\Http\Requests\PublishPolicyVersionRequest;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Models\OrganizationModel;

final class LegalAdminWebController
{
    private const array POLICY_LABELS = [
        'privacy_policy' => 'Política de privacidad',
        'terms_of_service' => 'Condiciones de uso',
        'minor_consent' => 'Consentimiento para menores',
    ];

    public function policies(QueryBus $queryBus): View
    {
        $policies = $queryBus->ask(new ListPoliciesQuery);
        assert(is_array($policies));

        return view('legal.admin-policies', [
            'policies' => array_map(
                static fn (PolicyResponse $policy): array => $policy->toArray(),
                $policies,
            ),
            'policyLabels' => self::POLICY_LABELS,
        ]);
    }

    public function publish(PublishPolicyVersionRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $data = $request->validated();

        try {
            $commandBus->dispatch(new PublishPolicyVersionCommand(
                key: (string) $data['key'],
                effectiveAt: isset($data['effective_at']) ? (string) $data['effective_at'] : null,
            ));
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Nueva versión de la política publicada correctamente.');
    }

    public function minors(Request $request, QueryBus $queryBus): View
    {
        $organizationId = $request->query('organization_id');
        $organizationId = is_string($organizationId) && $organizationId !== '' ? $organizationId : null;
        $minors = [];

        if ($organizationId !== null) {
            $result = $queryBus->ask(new GetOrganizationMinorsConsentsQuery(organizationId: $organizationId));
            assert(is_array($result));
            $minors = array_map(
                static fn (OrganizationMinorConsentsResponse $minor): array => $minor->toArray(),
                $result,
            );
        }

        return view('legal.admin-minors', [
            'organizationId' => $organizationId,
            'minors' => $minors,
            'organizations' => OrganizationModel::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
