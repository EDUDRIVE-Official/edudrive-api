<?php

declare(strict_types=1);

namespace Modules\Integration\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Modules\Integration\Application\Commands\ReactivateApiConsumerCommand;
use Modules\Integration\Application\Commands\RegisterApiConsumerCommand;
use Modules\Integration\Application\Commands\RevokeApiConsumerCommand;
use Modules\Integration\Application\Commands\RotateApiConsumerIntegrationKeyCommand;
use Modules\Integration\Application\Commands\SuspendApiConsumerCommand;
use Modules\Integration\Application\Queries\ListApiConsumersQuery;
use Modules\Integration\Application\Responses\ApiConsumerResponse;
use Modules\Integration\Presentation\Http\Requests\RegisterApiConsumerRequest;
use Modules\Integration\Presentation\Http\Requests\RevokeApiConsumerRequest;
use Modules\Integration\Presentation\Http\Requests\SuspendApiConsumerRequest;

final class ApiConsumerWebController
{
    public function index(QueryBus $queryBus): View
    {
        $consumers = $queryBus->ask(new ListApiConsumersQuery);
        assert(is_array($consumers));

        return view('integrations.index', [
            'consumers' => array_map(static fn (ApiConsumerResponse $consumer): array => $consumer->toArray(), $consumers),
            'scopes' => [
                'enrollments.manage' => 'Administrar matrículas',
                'enrollments.view' => 'Consultar matrículas',
                'certifications.view' => 'Consultar certificados',
                'road_passports.view' => 'Consultar pasaportes viales',
                'reports.view' => 'Consultar reportes',
            ],
        ]);
    }

    public function store(RegisterApiConsumerRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $data = $request->validated();

        try {
            $result = $commandBus->dispatch(new RegisterApiConsumerCommand(
                name: (string) $data['name'],
                scopes: $data['scopes'],
                expiresAt: isset($data['expires_at']) ? (string) $data['expires_at'] : null,
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ));
            assert($result instanceof ApiConsumerResponse);
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Integración registrada. Copiá la llave ahora: no volverá a mostrarse.')
            ->with('integration_key', $result->integrationKey);
    }

    public function suspend(string $consumerId, SuspendApiConsumerRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $data = $request->validated();

        return $this->runTransition(fn (): mixed => $commandBus->dispatch(new SuspendApiConsumerCommand(
            consumerId: $consumerId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        )), 'Integración suspendida.');
    }

    public function reactivate(string $consumerId, Request $request, CommandBus $commandBus): RedirectResponse
    {
        return $this->runTransition(fn (): mixed => $commandBus->dispatch(new ReactivateApiConsumerCommand(
            consumerId: $consumerId,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        )), 'Integración reactivada.');
    }

    public function revoke(string $consumerId, RevokeApiConsumerRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $data = $request->validated();

        return $this->runTransition(fn (): mixed => $commandBus->dispatch(new RevokeApiConsumerCommand(
            consumerId: $consumerId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        )), 'Integración revocada definitivamente.');
    }

    public function rotateKey(string $consumerId, Request $request, CommandBus $commandBus): RedirectResponse
    {
        try {
            $result = $commandBus->dispatch(new RotateApiConsumerIntegrationKeyCommand(
                consumerId: $consumerId,
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ));
            assert($result instanceof ApiConsumerResponse);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Llave rotada. Copiá la nueva llave ahora: no volverá a mostrarse.')
            ->with('integration_key', $result->integrationKey);
    }

    /** @param callable(): mixed $operation */
    private function runTransition(callable $operation, string $message): RedirectResponse
    {
        try {
            $operation();
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', $message);
    }
}
