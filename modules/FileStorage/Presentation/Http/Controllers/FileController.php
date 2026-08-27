<?php

declare(strict_types=1);

namespace Modules\FileStorage\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\FileStorage\Application\Commands\DeleteFileCommand;
use Modules\FileStorage\Application\Commands\SetFileScanStatusCommand;
use Modules\FileStorage\Application\Commands\UploadFileCommand;
use Modules\FileStorage\Application\Queries\GetFileDownloadUrlQuery;
use Modules\FileStorage\Application\Queries\GetFileQuery;
use Modules\FileStorage\Application\Queries\GetMyFilesQuery;
use Modules\FileStorage\Application\Responses\FileDownloadUrlResponse;
use Modules\FileStorage\Application\Responses\FileResponse;
use Modules\FileStorage\Presentation\Http\Requests\SetFileScanStatusRequest;
use Modules\FileStorage\Presentation\Http\Requests\UploadFileRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class FileController
{
    public function store(UploadFileRequest $request, CommandBus $commandBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $file = $request->file('file');
        assert($file !== null);

        $result = $commandBus->dispatch(new UploadFileCommand(
            ownerId: (string) $user->getAuthIdentifier(),
            originalFilename: (string) $file->getClientOriginalName(),
            mimeType: (string) $file->getMimeType(),
            sizeBytes: (int) $file->getSize(),
            localTmpPath: (string) $file->getRealPath(),
        ));
        assert($result instanceof FileResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function mine(Request $request, QueryBus $queryBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetMyFilesQuery(ownerId: (string) $user->getAuthIdentifier()));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (FileResponse $file): array => $file->toArray(),
            $result,
        )]);
    }

    public function show(
        string $fileId,
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetFileQuery(
            fileId: $fileId,
            requestingUserId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewFiles),
        ));
        assert($result instanceof FileResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function downloadUrl(
        string $fileId,
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetFileDownloadUrlQuery(
            fileId: $fileId,
            requestingUserId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewFiles),
        ));
        assert($result instanceof FileDownloadUrlResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function destroy(
        string $fileId,
        Request $request,
        CommandBus $commandBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $commandBus->dispatch(new DeleteFileCommand(
            fileId: $fileId,
            requestingUserId: (string) $user->getAuthIdentifier(),
            canManageOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ManageFiles),
        ));

        return response()->json(['data' => null]);
    }

    public function setScanStatus(string $fileId, SetFileScanStatusRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new SetFileScanStatusCommand(
            fileId: $fileId,
            scanStatus: (string) $data['scan_status'],
        ));
        assert($result instanceof FileResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
