<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Academic\Application\Commands\ApproveCourseCommand;
use Modules\Academic\Application\Commands\ArchiveCourseCommand;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Commands\SubmitCourseForReviewCommand;
use Modules\Academic\Application\Queries\ListCoursesQuery;
use Modules\Academic\Application\Responses\CourseListItemResponse;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Presentation\Http\Requests\CreateCourseRequest;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseWebController
{
    public function index(
        QueryBus $queryBus,
        PermissionChecker $checker,
    ): View {
        $result = $queryBus->ask(
            new ListCoursesQuery,
        );

        assert(is_array($result));

        /** @var list<CourseListItemResponse> $result */
        $courses = array_map(
            static fn (CourseListItemResponse $course): array => $course->toArray(),
            $result,
        );

        return view('courses.index', [
            'courses' => $courses,
            'canManage' => $checker->userHasPermission(
                (string) auth()->id(),
                Permission::ManageCourses,
            ),
        ]);
    }

    public function create(): View
    {
        return view('courses.create', [
            'modalities' => CourseModality::cases(),
        ]);
    }

    public function store(
        CreateCourseRequest $request,
        CommandBus $commandBus,
    ): RedirectResponse {
        $validated = $request->validated();

        $commandBus->dispatch(
            new CreateCourseCommand(
                code: (string) $validated['code'],
                title: (string) $validated['title'],
                description: isset($validated['description'])
                    ? (string) $validated['description']
                    : null,
                objectives: isset($validated['objectives'])
                    ? (string) $validated['objectives']
                    : null,
                prerequisites: isset($validated['prerequisites'])
                    ? (string) $validated['prerequisites']
                    : null,
                modality: isset($validated['modality'])
                    ? (string) $validated['modality']
                    : null,
                durationHours: isset($validated['duration_hours'])
                    ? (int) $validated['duration_hours']
                    : null,
            ),
        );

        return redirect()
            ->route('courses.index')
            ->with('status', 'Curso creado correctamente.');
    }

    public function submitForReview(
        string $courseId,
        CommandBus $commandBus,
    ): RedirectResponse {
        try {
            $commandBus->dispatch(
                new SubmitCourseForReviewCommand(courseId: $courseId),
            );
        } catch (DomainException $exception) {
            return redirect()
                ->route('courses.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('courses.index')
            ->with('status', 'Curso enviado a revisión correctamente.');
    }

    public function approve(
        string $courseId,
        CommandBus $commandBus,
    ): RedirectResponse {
        try {
            $commandBus->dispatch(
                new ApproveCourseCommand(courseId: $courseId),
            );
        } catch (DomainException $exception) {
            return redirect()
                ->route('courses.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('courses.index')
            ->with('status', 'Curso aprobado correctamente.');
    }

    public function publish(
        string $courseId,
        CommandBus $commandBus,
    ): RedirectResponse {
        try {
            $commandBus->dispatch(
                new PublishCourseCommand(courseId: $courseId),
            );
        } catch (DomainException $exception) {
            return redirect()
                ->route('courses.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('courses.index')
            ->with('status', 'Curso publicado correctamente.');
    }

    public function archive(
        string $courseId,
        CommandBus $commandBus,
    ): RedirectResponse {
        try {
            $commandBus->dispatch(
                new ArchiveCourseCommand(courseId: $courseId),
            );
        } catch (DomainException $exception) {
            return redirect()
                ->route('courses.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('courses.index')
            ->with('status', 'Curso archivado correctamente.');
    }
}
