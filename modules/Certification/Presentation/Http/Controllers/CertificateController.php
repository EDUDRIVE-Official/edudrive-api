<?php

declare(strict_types=1);

namespace Modules\Certification\Presentation\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Certification\Application\Commands\IssueCertificateCommand;
use Modules\Certification\Application\Commands\RevokeCertificateCommand;
use Modules\Certification\Application\Queries\GetCertificateQuery;
use Modules\Certification\Application\Queries\GetMyCertificatesQuery;
use Modules\Certification\Application\Queries\VerifyCertificateQuery;
use Modules\Certification\Application\Responses\CertificateResponse;
use Modules\Certification\Application\Responses\CertificateVerificationResponse;
use Modules\Certification\Presentation\Http\Requests\IssueCertificateRequest;
use Modules\Certification\Presentation\Http\Requests\RevokeCertificateRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class CertificateController
{
    public function store(IssueCertificateRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new IssueCertificateCommand(
            userId: (string) $data['user_id'],
            courseId: (string) $data['course_id'],
            expiresAt: isset($data['expires_at']) ? new DateTimeImmutable((string) $data['expires_at']) : null,
        ));
        assert($result instanceof CertificateResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function me(Request $request, QueryBus $queryBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetMyCertificatesQuery(userId: (string) $user->getAuthIdentifier()));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (CertificateResponse $certificate): array => $certificate->toArray(),
            $result,
        )]);
    }

    public function show(
        string $certificateId,
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetCertificateQuery(
            certificateId: $certificateId,
            userId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewCertifications),
        ));
        assert($result instanceof CertificateResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function revoke(string $certificateId, RevokeCertificateRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RevokeCertificateCommand(
            certificateId: $certificateId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        ));
        assert($result instanceof CertificateResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function verify(string $validationCode, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new VerifyCertificateQuery(validationCode: $validationCode));
        assert($result instanceof CertificateVerificationResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
