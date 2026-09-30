<?php

declare(strict_types=1);

namespace Modules\FileStorage\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\FileStorage\Application\Commands\DeleteFileCommand;
use Modules\FileStorage\Application\Commands\UploadFileCommand;
use Modules\FileStorage\Application\Queries\GetFileDownloadUrlQuery;
use Modules\FileStorage\Application\Queries\GetFileQuery;
use Modules\FileStorage\Application\Queries\GetMyFilesQuery;
use Modules\FileStorage\Application\Responses\FileDownloadUrlResponse;
use Modules\FileStorage\Application\Responses\FileResponse;
use Modules\FileStorage\Presentation\Http\Requests\UploadFileRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Domain\Exceptions\DomainException;

final class FileWebController
{
    public function index(Request $request, QueryBus $queryBus): View
    {
        $files = $queryBus->ask(new GetMyFilesQuery(ownerId: (string) $request->user()?->getAuthIdentifier()));
        assert(is_array($files));

        return view('files.index', [
            'files' => array_reverse(array_map(static fn (FileResponse $file): array => $file->toArray(), $files)),
        ]);
    }

    public function store(UploadFileRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $file = $request->file('file');
        assert($file !== null);

        try {
            $commandBus->dispatch(new UploadFileCommand(
                ownerId: (string) $request->user()?->getAuthIdentifier(),
                originalFilename: (string) $file->getClientOriginalName(),
                mimeType: (string) $file->getMimeType(),
                sizeBytes: (int) $file->getSize(),
                localTmpPath: (string) $file->getRealPath(),
            ));
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Archivo cargado. Estará disponible para descarga después del análisis de seguridad.');
    }

    public function download(string $fileId, Request $request, QueryBus $queryBus, PermissionChecker $permissionChecker): RedirectResponse
    {
        $userId = (string) $request->user()?->getAuthIdentifier();

        try {
            $file = $queryBus->ask(new GetFileQuery(
                fileId: $fileId,
                requestingUserId: $userId,
                canViewOthers: $permissionChecker->userHasPermission($userId, Permission::ViewFiles),
            ));
            assert($file instanceof FileResponse);
            if ($file->scanStatus !== 'clean') {
                return back()->with('error', 'El archivo no puede descargarse hasta completar el análisis de seguridad.');
            }

            $result = $queryBus->ask(new GetFileDownloadUrlQuery(
                fileId: $fileId,
                requestingUserId: $userId,
                canViewOthers: $permissionChecker->userHasPermission($userId, Permission::ViewFiles),
            ));
            assert($result instanceof FileDownloadUrlResponse);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->away($result->url);
    }

    public function destroy(string $fileId, Request $request, CommandBus $commandBus, PermissionChecker $permissionChecker): RedirectResponse
    {
        $userId = (string) $request->user()?->getAuthIdentifier();

        try {
            $commandBus->dispatch(new DeleteFileCommand(
                fileId: $fileId,
                requestingUserId: $userId,
                canManageOthers: $permissionChecker->userHasPermission($userId, Permission::ManageFiles),
            ));
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Archivo eliminado correctamente.');
    }
}
