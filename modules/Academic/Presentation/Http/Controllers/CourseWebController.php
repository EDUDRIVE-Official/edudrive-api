<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Academic\Application\Queries\ListCoursesQuery;
use Modules\Academic\Application\Responses\CourseListItemResponse;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\QueryBus;

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
}
