<?php

declare(strict_types=1);

namespace Modules\Legal\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Modules\Legal\Application\Commands\RecordConsentCommand;
use Modules\Legal\Application\Commands\RevokeConsentCommand;
use Modules\Legal\Application\Queries\GetMyConsentsQuery;
use Modules\Legal\Application\Queries\ListPoliciesQuery;
use Modules\Legal\Application\Responses\ConsentResponse;
use Modules\Legal\Application\Responses\PolicyResponse;
use Modules\Legal\Presentation\Http\Requests\RecordConsentRequest;

final class LegalConsentWebController
{
    public function index(Request $request, QueryBus $queryBus): View
    {
        $userId = (string) $request->user()?->getAuthIdentifier();
        $policies = $queryBus->ask(new ListPoliciesQuery);
        $consents = $queryBus->ask(new GetMyConsentsQuery(userId: $userId));
        assert(is_array($policies));
        assert(is_array($consents));

        $consentsByPolicy = collect($consents)->groupBy(
            static fn (ConsentResponse $consent): string => $consent->policyKey,
        );

        return view('legal.consents', [
            'policies' => array_map(
                static function (PolicyResponse $policy) use ($consentsByPolicy): array {
                    $policyConsents = $consentsByPolicy->get($policy->key, collect());
                    $active = $policyConsents->first(
                        static fn (ConsentResponse $consent): bool => $consent->policyVersion === $policy->version
                            && $consent->revokedAt === null,
                    );

                    return array_merge($policy->toArray(), [
                        'active_consent' => $active instanceof ConsentResponse ? $active->toArray() : null,
                    ]);
                },
                $policies,
            ),
            'consentHistory' => array_map(
                static fn (ConsentResponse $consent): array => $consent->toArray(),
                $consents,
            ),
        ]);
    }

    public function accept(RecordConsentRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $data = $request->validated();

        try {
            $commandBus->dispatch(new RecordConsentCommand(
                userId: (string) $request->user()?->getAuthIdentifier(),
                policyKey: (string) $data['policy_key'],
                guardianDeclaration: isset($data['guardian_declaration']) ? (string) $data['guardian_declaration'] : null,
            ));
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Consentimiento registrado correctamente.');
    }

    public function revoke(string $policyKey, Request $request, CommandBus $commandBus): RedirectResponse
    {
        try {
            $commandBus->dispatch(new RevokeConsentCommand(
                userId: (string) $request->user()?->getAuthIdentifier(),
                policyKey: $policyKey,
            ));
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Consentimiento revocado correctamente.');
    }
}
