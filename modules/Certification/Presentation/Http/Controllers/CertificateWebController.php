<?php

declare(strict_types=1);

namespace Modules\Certification\Presentation\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseModel;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleAssignmentModel;
use Modules\Certification\Application\Commands\IssueCertificateCommand;
use Modules\Certification\Application\Commands\RevokeCertificateCommand;
use Modules\Certification\Application\Exceptions\CertificateNotFound;
use Modules\Certification\Application\Queries\GetCertificateQuery;
use Modules\Certification\Application\Queries\GetMyCertificatesQuery;
use Modules\Certification\Application\Queries\VerifyCertificateQuery;
use Modules\Certification\Application\Responses\CertificateResponse;
use Modules\Certification\Application\Responses\CertificateVerificationResponse;
use Modules\Certification\Infrastructure\Persistence\Eloquent\Models\CertificateModel;
use Modules\Certification\Presentation\Http\Requests\IssueCertificateRequest;
use Modules\Certification\Presentation\Http\Requests\RevokeCertificateRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

final class CertificateWebController
{
    public function index(Request $request, QueryBus $queryBus, CourseRepository $courses): View
    {
        $result = $queryBus->ask(new GetMyCertificatesQuery(
            userId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert(is_array($result));

        return view('certificates.index', [
            'certificates' => array_map(
                static function (CertificateResponse $certificate) use ($courses): array {
                    $data = $certificate->toArray();
                    $course = $courses->findById(CourseId::fromString($certificate->courseId));
                    $data['course_title'] = $course?->title()->value() ?? $certificate->courseId;

                    return $data;
                },
                $result,
            ),
        ]);
    }

    public function verify(Request $request, QueryBus $queryBus, CourseRepository $courses): View
    {
        $code = $request->query('code');
        $code = is_string($code) && $code !== '' ? strtoupper($code) : null;
        $certificate = null;
        $notFound = false;

        if ($code !== null) {
            try {
                $result = $queryBus->ask(new VerifyCertificateQuery(validationCode: $code));
                assert($result instanceof CertificateVerificationResponse);
                $certificate = $result->toArray();
                $course = $courses->findById(CourseId::fromString($result->courseId));
                $certificate['course_objectives'] = $course?->objectives();
                $certificate['course_duration_hours'] = $course?->durationHours();
            } catch (CertificateNotFound) {
                $notFound = true;
            }
        }

        return view('certificates.verify', compact('code', 'certificate', 'notFound'));
    }

    public function show(
        string $certificateId,
        Request $request,
        QueryBus $queryBus,
        CourseRepository $courses,
        UserRepository $users,
    ): View {
        try {
            $result = $queryBus->ask(new GetCertificateQuery(
                certificateId: $certificateId,
                userId: (string) $request->user()?->getAuthIdentifier(),
                canViewOthers: false,
            ));
        } catch (CertificateNotFound) {
            abort(404);
        }
        assert($result instanceof CertificateResponse);

        $certificate = $result->toArray();
        $course = $courses->findById(CourseId::fromString($result->courseId));
        $holder = $result->userId === null ? null : $users->findById($result->userId);

        return view('certificates.show', [
            'certificate' => $certificate,
            'courseName' => $course?->title()->value() ?? 'Formación en educación vial',
            'courseObjectives' => $course?->objectives(),
            'courseDurationHours' => $course?->durationHours(),
            'holderName' => $holder?->name() ?? 'Titular de EDUDRIVE',
            'verificationUrl' => route('certificates.verify', ['code' => $result->validationCode]),
        ]);
    }

    public function search(Request $request, QueryBus $queryBus, PermissionChecker $checker): View
    {
        $certificateId = $request->query('certificate_id');
        $certificateId = is_string($certificateId) && $certificateId !== '' ? $certificateId : null;
        $certificate = null;
        $notFound = false;
        $userId = (string) $request->user()?->getAuthIdentifier();

        if ($certificateId !== null) {
            try {
                $result = $queryBus->ask(new GetCertificateQuery(
                    certificateId: $certificateId,
                    userId: $userId,
                    canViewOthers: true,
                ));
                assert($result instanceof CertificateResponse);
                $certificate = $result->toArray();
            } catch (CertificateNotFound) {
                $notFound = true;
            }
        }

        return view('certificates.admin', [
            'searchedCertificateId' => $certificateId,
            'certificate' => $certificate,
            'notFound' => $notFound,
            'canManage' => $checker->userHasPermission($userId, Permission::ManageCertifications),
            'users' => UserModel::query()
                ->whereIn('id', RoleAssignmentModel::query()->select('user_id')->where('role', Role::Student->value))
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'status']),
            'courses' => CourseModel::query()->orderBy('title')->get(['id', 'title', 'status']),
            'certificateUserName' => isset($certificate['user_id'])
                ? UserModel::query()->whereKey($certificate['user_id'])->value('name')
                : null,
            'certificateCourseName' => isset($certificate['course_id'])
                ? CourseModel::query()->whereKey($certificate['course_id'])->value('title')
                : null,
            'certificateOptions' => CertificateModel::query()
                ->orderByDesc('issued_at')
                ->get(['id', 'user_id', 'course_id', 'validation_code', 'status'])
                ->map(static fn (CertificateModel $item): array => [
                    'id' => (string) $item->id,
                    'student' => UserModel::query()->whereKey($item->user_id)->value('name') ?? 'Usuario eliminado',
                    'course' => CourseModel::query()->whereKey($item->course_id)->value('title') ?? 'Curso no disponible',
                    'code' => (string) $item->validation_code,
                    'status' => (string) $item->status,
                ]),
        ]);
    }

    public function issue(IssueCertificateRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $data = $request->validated();

        try {
            $result = $commandBus->dispatch(new IssueCertificateCommand(
                userId: (string) $data['user_id'],
                courseId: (string) $data['course_id'],
                expiresAt: isset($data['expires_at']) ? new DateTimeImmutable((string) $data['expires_at']) : null,
            ));
            assert($result instanceof CertificateResponse);
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('certificates.admin.search', ['certificate_id' => $result->id])
            ->with('status', 'Certificado emitido correctamente.');
    }

    public function revoke(
        string $certificateId,
        RevokeCertificateRequest $request,
        CommandBus $commandBus,
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $commandBus->dispatch(new RevokeCertificateCommand(
                certificateId: $certificateId,
                reason: isset($data['reason']) ? (string) $data['reason'] : null,
            ));
        } catch (DomainException $exception) {
            return redirect()->route('certificates.admin.search', ['certificate_id' => $certificateId])
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('certificates.admin.search', ['certificate_id' => $certificateId])
            ->with('status', 'Certificado revocado correctamente.');
    }
}
