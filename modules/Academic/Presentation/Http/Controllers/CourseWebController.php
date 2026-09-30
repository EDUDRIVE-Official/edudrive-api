<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Academic\Application\Commands\ActivateEnrollmentCommand;
use Modules\Academic\Application\Commands\ApproveCourseCommand;
use Modules\Academic\Application\Commands\ArchiveCourseCommand;
use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Commands\CreateEnrollmentCommand;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Commands\SubmitCourseForReviewCommand;
use Modules\Academic\Application\Queries\GetCourseCurriculumQuery;
use Modules\Academic\Application\Queries\GetEnrollmentCurriculumStatusQuery;
use Modules\Academic\Application\Queries\GetEnrollmentProgressQuery;
use Modules\Academic\Application\Queries\GetUnitContentQuery;
use Modules\Academic\Application\Queries\ListCoursesQuery;
use Modules\Academic\Application\Responses\CourseCurriculumResponse;
use Modules\Academic\Application\Responses\CourseListItemResponse;
use Modules\Academic\Application\Responses\CurriculumUnlockResponse;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Application\Responses\EnrollmentResponse;
use Modules\Academic\Application\Responses\UnitContentResponse;
use Modules\Academic\Application\Services\LearnerStageResolver;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Infrastructure\Services\CourseAudienceCatalog;
use Modules\Academic\Presentation\Http\Requests\CreateCourseRequest;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Modules\Identity\Domain\Repositories\StudentProfileRepository;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\GuardianRelationshipModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Domain\ValueObjects\LearningVerb;
use Modules\RoadPassport\Application\DTO\EvidenceEntry;
use Modules\RoadPassport\Application\Services\RoadPassportEvidenceRecorder;
use Modules\RoadPassport\Domain\Enums\EvidenceType;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;

final class CourseWebController
{
    public function index(
        QueryBus $queryBus,
        PermissionChecker $checker,
        EnrollmentRepository $enrollments,
        LearnerStageResolver $stageResolver,
        CourseAudienceCatalog $audiences,
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

        $userId = (string) auth()->id();
        $learnerStage = $this->learnerStage($stageResolver);
        $courseAudiences = $audiences->forCourses(array_column($courses, 'id'));
        $recommendationAssigned = false;
        foreach ($courses as &$course) {
            $course['audience'] = $courseAudiences[$course['id']];
            $enrollment = $this->enrollmentForUser($enrollments, $course['id'], $userId);
            $course['enrollment'] = $enrollment;
            $course['progress'] = null;
            if ($enrollment !== null && in_array($enrollment['status'], [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value], true)) {
                $progress = $queryBus->ask(new GetEnrollmentProgressQuery(
                    enrollmentId: $enrollment['id'],
                    userId: $userId,
                    canViewOthers: false,
                ));
                assert($progress instanceof EnrollmentProgressResponse);
                $course['progress'] = $progress->toArray();
            }

            $course['is_experience'] = str_starts_with($course['code'], 'EDU-EXP-');
            $course['is_recommended'] = false;
            if (! $recommendationAssigned && $course['audience']['stage'] !== null && $this->matchesCurriculumAudience($course['audience']['stage']) && $course['status'] === CourseStatus::Published->value && ($course['progress']['progress_percentage'] ?? 0) < 100) {
                $course['is_recommended'] = true;
                $recommendationAssigned = true;
            }
        }
        unset($course);

        usort($courses, static fn (array $left, array $right): int => [$right['is_recommended'], $right['is_experience']] <=> [$left['is_recommended'], $left['is_experience']]);

        $canManage = $checker->userHasPermission($userId, Permission::ManageCourses);

        return view('courses.index', [
            'courses' => $courses,
            'canManage' => $canManage,
            'learnerStage' => $learnerStage,
            'learningPurpose' => app(StudentProfileRepository::class)->findByUserId($userId)?->learningPurpose(),
        ]);
    }

    public function create(): View
    {
        return view('courses.create', [
            'modalities' => CourseModality::cases(),
        ]);
    }

    public function show(
        string $courseId,
        QueryBus $queryBus,
        EnrollmentRepository $enrollments,
        PermissionChecker $checker,
    ): View {
        $result = $queryBus->ask(new GetCourseCurriculumQuery(courseId: $courseId));
        assert($result instanceof CourseCurriculumResponse);

        $course = $result->toArray();
        $lessonsByUnit = [];

        foreach ($course['modules'] as $module) {
            foreach ($module['units'] as $unit) {
                $content = $queryBus->ask(new GetUnitContentQuery($courseId, $unit['id']));
                assert($content instanceof UnitContentResponse);
                $lessonsByUnit[$unit['id']] = $content->toArray()['lessons'];
            }
        }

        return view('courses.show', [
            'course' => $course,
            'lessonsByUnit' => $lessonsByUnit,
            'enrollment' => $this->enrollmentForUser($enrollments, $courseId, (string) auth()->id()),
            'canManage' => $checker->userHasPermission((string) auth()->id(), Permission::ManageCourses),
        ]);
    }

    public function preview(
        string $courseId,
        QueryBus $queryBus,
        LearnerStageResolver $stageResolver,
    ): View {
        $result = $queryBus->ask(new GetCourseCurriculumQuery(courseId: $courseId));
        assert($result instanceof CourseCurriculumResponse);

        $course = $result->toArray();
        $lessonsByUnit = [];
        $unlockByUnit = [];
        $totalLessons = 0;

        foreach ($course['modules'] as $module) {
            foreach ($module['units'] as $unit) {
                $content = $queryBus->ask(new GetUnitContentQuery($courseId, $unit['id']));
                assert($content instanceof UnitContentResponse);
                $lessons = $content->toArray()['lessons'];
                $lessonsByUnit[$unit['id']] = $lessons;
                $totalLessons += count($lessons);
                $unlockByUnit[$unit['id']] = [
                    'unit_id' => $unit['id'],
                    'unlocked' => true,
                ];
            }
        }

        return view('courses.learn', [
            'adminPreview' => true,
            'course' => $course,
            'enrollment' => null,
            'lessonsByUnit' => $lessonsByUnit,
            'progress' => [
                'completed_lessons' => [],
                'completed_lessons_count' => 0,
                'total_lessons' => $totalLessons,
                'progress_percentage' => 0,
            ],
            'learnerStage' => $this->learnerStage($stageResolver),
            'reinforcement' => null,
            'selfAssessmentSummary' => [
                'necesito_practicar' => 0,
                'voy_avanzando' => 0,
                'puedo_aplicarlo' => 0,
            ],
            'entryDiagnosticRecorded' => false,
            'transferCheckRecorded' => false,
            'hasGuardian' => false,
            'unlockByUnit' => $unlockByUnit,
        ]);
    }

    public function enroll(
        string $courseId,
        QueryBus $queryBus,
        CommandBus $commandBus,
        EnrollmentRepository $enrollments,
    ): RedirectResponse {
        $course = $queryBus->ask(new GetCourseCurriculumQuery(courseId: $courseId));
        assert($course instanceof CourseCurriculumResponse);

        if (CourseStatus::from($course->status) !== CourseStatus::Published) {
            return redirect()->route('courses.show', $courseId)
                ->with('error', 'Este curso todavía no está disponible para inscripción.');
        }

        $existing = $this->enrollmentForUser($enrollments, $courseId, (string) auth()->id());

        if ($existing !== null) {
            if ($existing['status'] === EnrollmentStatus::Pending->value) {
                $commandBus->dispatch(new ActivateEnrollmentCommand($existing['id']));
            }

            return redirect()->route('courses.learn', $existing['id']);
        }

        $result = $commandBus->dispatch(new CreateEnrollmentCommand(
            courseId: $courseId,
            userId: (string) auth()->id(),
            status: EnrollmentStatus::Active->value,
            source: 'individual',
        ));
        assert($result instanceof EnrollmentResponse);

        return redirect()->route('courses.learn', $result->id)
            ->with('status', 'Inscripción completada. Ya puedes comenzar el curso.');
    }

    public function learn(
        string $enrollmentId,
        QueryBus $queryBus,
        EnrollmentRepository $enrollments,
        LearnerStageResolver $stageResolver,
        LearningEventRepository $learningEvents,
        RoadPassportRepository $passports,
    ): View {
        $enrollment = $enrollments->findById(EnrollmentId::fromString($enrollmentId));

        if ($enrollment === null || $enrollment->userId() !== (string) auth()->id()) {
            abort(404);
        }

        $courseResult = $queryBus->ask(new GetCourseCurriculumQuery($enrollment->courseId()->value()));
        assert($courseResult instanceof CourseCurriculumResponse);
        $course = $courseResult->toArray();
        $lessonsByUnit = [];

        foreach ($course['modules'] as $module) {
            foreach ($module['units'] as $unit) {
                $content = $queryBus->ask(new GetUnitContentQuery($course['id'], $unit['id']));
                assert($content instanceof UnitContentResponse);
                $lessonsByUnit[$unit['id']] = $content->toArray()['lessons'];
            }
        }

        $progress = $queryBus->ask(new GetEnrollmentProgressQuery(
            enrollmentId: $enrollmentId,
            userId: (string) auth()->id(),
            canViewOthers: false,
        ));
        assert($progress instanceof EnrollmentProgressResponse);

        $unlock = $queryBus->ask(new GetEnrollmentCurriculumStatusQuery(
            enrollmentId: $enrollmentId,
            userId: (string) auth()->id(),
            canViewOthers: false,
        ));
        assert($unlock instanceof CurriculumUnlockResponse);

        $reinforcement = null;
        $selfAssessmentSummary = [
            'necesito_practicar' => 0,
            'voy_avanzando' => 0,
            'puedo_aplicarlo' => 0,
        ];
        $assessedLessons = [];
        foreach ($learningEvents->findByEnrollmentId($enrollmentId) as $event) {
            if ($event->verb() !== LearningVerb::LessonCompleted || isset($assessedLessons[$event->subjectId()])) {
                continue;
            }

            $assessedLessons[$event->subjectId()] = true;
            $assessment = $event->evidence()['self_assessment'] ?? null;
            if (! is_string($assessment) || ! array_key_exists($assessment, $selfAssessmentSummary)) {
                continue;
            }

            $selfAssessmentSummary[$assessment]++;
            if ($assessment === 'necesito_practicar' && $reinforcement === null) {
                $reinforcement = [
                    'lesson_id' => $event->subjectId(),
                    'lesson_title' => (string) ($event->evidence()['lesson_title'] ?? 'la lección anterior'),
                    'reason' => 'La marcaste como “Necesito practicar”. Repasarla es parte normal del aprendizaje y no cambia tu avance.',
                ];
            }
        }

        $passport = $passports->findByUserId((string) auth()->id());
        $passportEvidence = $passport?->evidence() ?? [];
        $entryDiagnosticRecorded = false;
        $transferCheckRecorded = false;
        foreach ($passportEvidence as $evidence) {
            if ($evidence->courseId !== $course['id']) {
                continue;
            }
            $kind = $evidence->details['evidence_kind'] ?? null;
            if ($kind === 'course_entry_diagnostic') {
                $entryDiagnosticRecorded = true;
            }
            if ($kind === 'course_transfer_check') {
                $transferCheckRecorded = true;
            }
        }

        return view('courses.learn', [
            'course' => $course,
            'enrollment' => $enrollment,
            'lessonsByUnit' => $lessonsByUnit,
            'progress' => $progress->toArray(),
            'learnerStage' => $this->learnerStage($stageResolver),
            'reinforcement' => $reinforcement,
            'selfAssessmentSummary' => $selfAssessmentSummary,
            'entryDiagnosticRecorded' => $entryDiagnosticRecorded,
            'transferCheckRecorded' => $transferCheckRecorded,
            'hasGuardian' => GuardianRelationshipModel::query()
                ->where('minor_user_id', (string) auth()->id())
                ->whereNull('revoked_at')
                ->exists(),
            'unlockByUnit' => collect($unlock->toArray()['modules'])
                ->flatMap(fn (array $module): array => $module['units'])
                ->keyBy('unit_id')
                ->all(),
        ]);
    }

    private function matchesCurriculumAudience(string $audience): bool
    {
        $user = auth()->user();
        if (! $user instanceof UserModel || $user->date_of_birth === null || $user->date_of_birth->isFuture()) {
            return false;
        }
        $age = (int) $user->date_of_birth->age;
        // Existing course bands are content metadata, not the new curricular stages.
        // Adult courses need explicit role mapping before automatic recommendation.
        return match ($audience) {
            'explore' => $age >= 5 && $age <= 6,
            'discover' => $age >= 9 && $age <= 12,
            'understand' => $age >= 13 && $age <= 15,
            'prepare' => $age === 16,
            default => false,
        };
    }

    /** @return array{stage: string, identity: string, age_range: string, guidance: string, requires_guardian: bool, instruction: string, reflection_prompt: string, practice_mode: string} */
    private function learnerStage(LearnerStageResolver $resolver): array
    {
        $user = auth()->user();

        return $resolver->resolve($user instanceof UserModel ? $user->date_of_birth?->toDateTimeImmutable() : null);
    }

    public function completeLesson(
        string $enrollmentId,
        string $lessonId,
        Request $request,
        CommandBus $commandBus,
        QueryBus $queryBus,
    ): RedirectResponse {
        $validated = $request->validate([
            'reflection' => ['required', 'string', 'min:10', 'max:500'],
            'self_assessment' => ['required', 'in:necesito_practicar,voy_avanzando,puedo_aplicarlo'],
            'time_spent_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
        ], [
            'reflection.required' => 'Contá brevemente qué harías con este aprendizaje.',
            'reflection.min' => 'Tu reflexión debe tener al menos 10 caracteres.',
            'reflection.max' => 'Tu reflexión no puede superar los 500 caracteres.',
            'self_assessment.required' => 'Elegí cómo te sentís para aplicar esta conducta.',
            'self_assessment.in' => 'La autoevaluación seleccionada no es válida.',
            'time_spent_minutes.integer' => 'El tiempo de aprendizaje registrado no es válido.',
            'time_spent_minutes.min' => 'El tiempo mínimo registrado es de un minuto.',
            'time_spent_minutes.max' => 'Una sesión de aprendizaje no puede superar cuatro horas.',
        ]);
        $scenarioAnswers = [];
        foreach ((array) $request->input('scenario_answers', []) as $blockId => $choiceId) {
            if (is_string($blockId) && is_string($choiceId)) {
                $scenarioAnswers[$blockId] = $choiceId;
            }
        }

        try {
            $result = $commandBus->dispatch(new CompleteLessonCommand(
                enrollmentId: $enrollmentId,
                lessonId: $lessonId,
                userId: (string) auth()->id(),
                timeSpentMinutes: isset($validated['time_spent_minutes']) ? (int) $validated['time_spent_minutes'] : null,
                scenarioAnswers: $scenarioAnswers,
                reflection: isset($validated['reflection']) ? trim((string) $validated['reflection']) : null,
                selfAssessment: isset($validated['self_assessment']) ? (string) $validated['self_assessment'] : null,
            ));
            assert($result instanceof EnrollmentProgressResponse);
        } catch (DomainException $exception) {
            return redirect()->to(route('courses.learn', $enrollmentId).'#lesson-'.$lessonId)
                ->with('error', $exception->getMessage());
        }

        if ($result->progressPercentage === 100) {
            $anchor = 'mission-complete';
            $message = '¡Misión completada! El logro quedó registrado en tu Pasaporte Vial.';
        } else {
            $course = $queryBus->ask(new GetCourseCurriculumQuery($result->courseId));
            assert($course instanceof CourseCurriculumResponse);
            $anchor = 'lesson-'.($this->nextIncompleteLessonId($course->toArray(), $result->completedLessons, $queryBus) ?? $lessonId);
            $message = '¡Buen trabajo! La siguiente lección ya está lista.';
        }

        return redirect()->to(route('courses.learn', $enrollmentId).'#'.$anchor)->with('status', $message);
    }

    public function storeTransferCheck(
        string $enrollmentId,
        Request $request,
        EnrollmentRepository $enrollments,
        QueryBus $queryBus,
        RoadPassportEvidenceRecorder $evidence,
    ): RedirectResponse {
        $enrollment = $enrollments->findById(EnrollmentId::fromString($enrollmentId));
        if ($enrollment === null || $enrollment->userId() !== (string) auth()->id()) {
            abort(404);
        }

        $course = $queryBus->ask(new GetCourseCurriculumQuery($enrollment->courseId()->value()));
        assert($course instanceof CourseCurriculumResponse);
        if ($course->code !== 'EDU-EXP-001' || $enrollment->status() !== EnrollmentStatus::Completed) {
            abort(404);
        }

        $data = $request->validate([
            'answers' => ['required', 'array:visibility,priority,inclusion,selfcare'],
            'answers.visibility' => ['required', 'string', 'in:behind,marked,group'],
            'answers.priority' => ['required', 'string', 'in:claim,verify,wave'],
            'answers.inclusion' => ['required', 'string', 'in:quick,route,alone'],
            'answers.selfcare' => ['required', 'string', 'in:rush,pause,read'],
        ]);
        $correct = ['visibility' => 'marked', 'priority' => 'verify', 'inclusion' => 'route', 'selfcare' => 'pause'];
        $labels = ['visibility' => 'Percepción', 'priority' => 'Decisión', 'inclusion' => 'Convivencia', 'selfcare' => 'Autocuidado'];
        $strengths = [];
        $practice = [];
        foreach ($correct as $domain => $answer) {
            if (($data['answers'][$domain] ?? null) === $answer) {
                $strengths[] = $labels[$domain];
            } else {
                $practice[] = $labels[$domain];
            }
        }

        $evidence->record(new EvidenceEntry(
            userId: $enrollment->userId(),
            type: EvidenceType::StudentReflection,
            subjectId: hash('sha256', 'safe-crossing-transfer|'.$enrollmentId),
            courseId: $enrollment->courseId()->value(),
            details: [
                'lesson_title' => 'Misión final: una salida que cambia',
                'evidence_kind' => 'course_transfer_check',
                'score' => count($strengths),
                'maximum_score' => count($correct),
                'strengths' => $strengths,
                'practice_areas' => $practice,
                'indicator_codes' => ['PEATON.OBSERVA', 'RIESGO.MARGEN', 'CONVIVENCIA.CUIDA', 'AUTOCUIDADO.PAUSA'],
            ],
        ));

        return redirect()->to(route('courses.learn', $enrollmentId).'#transfer-check')
            ->with('status', 'Tu evaluación de transferencia quedó registrada en el Pasaporte Vial.');
    }

    public function storeEntryDiagnostic(
        string $enrollmentId,
        Request $request,
        EnrollmentRepository $enrollments,
        QueryBus $queryBus,
        RoadPassportEvidenceRecorder $evidence,
    ): RedirectResponse {
        $enrollment = $enrollments->findById(EnrollmentId::fromString($enrollmentId));
        if ($enrollment === null || $enrollment->userId() !== (string) auth()->id()) {
            abort(404);
        }

        $course = $queryBus->ask(new GetCourseCurriculumQuery($enrollment->courseId()->value()));
        assert($course instanceof CourseCurriculumResponse);
        if ($course->code !== 'EDU-EXP-001' || ! in_array($enrollment->status(), [EnrollmentStatus::Active, EnrollmentStatus::Completed], true)) {
            abort(404);
        }

        $data = $request->validate([
            'answers' => ['required', 'array:place,change,pressure'],
            'answers.place' => ['required', 'string', 'in:signal,view,follow'],
            'answers.change' => ['required', 'string', 'in:right,check,run'],
            'answers.pressure' => ['required', 'string', 'in:group,route,edge'],
        ]);
        $correct = ['place' => 'view', 'change' => 'check', 'pressure' => 'route'];
        $score = count(array_filter($correct, fn (string $answer, string $key): bool => ($data['answers'][$key] ?? null) === $answer, ARRAY_FILTER_USE_BOTH));

        $evidence->record(new EvidenceEntry(
            userId: $enrollment->userId(),
            type: EvidenceType::StudentReflection,
            subjectId: hash('sha256', 'safe-crossing-entry|'.$enrollmentId),
            courseId: $enrollment->courseId()->value(),
            details: [
                'lesson_title' => 'Diagnóstico inicial: cómo decidís hoy',
                'evidence_kind' => 'course_entry_diagnostic',
                'score' => $score,
                'maximum_score' => count($correct),
                'diagnostic_only' => true,
                'indicator_codes' => ['PEATON.OBSERVA', 'RIESGO.MARGEN', 'AUTOCUIDADO.PRESION'],
            ],
        ));

        return redirect()->to(route('courses.learn', $enrollmentId).'#entry-diagnostic')
            ->with('status', 'Guardamos tu punto de partida. No es una nota y no afecta tu progreso.');
    }

    /**
     * @param  array<string, mixed>  $course
     * @param  list<string>  $completedLessons
     */
    private function nextIncompleteLessonId(array $course, array $completedLessons, QueryBus $queryBus): ?string
    {
        foreach ($course['modules'] as $module) {
            foreach ($module['units'] as $unit) {
                $content = $queryBus->ask(new GetUnitContentQuery($course['id'], $unit['id']));
                assert($content instanceof UnitContentResponse);
                foreach ($content->toArray()['lessons'] as $lesson) {
                    if (! in_array($lesson['id'], $completedLessons, true)) {
                        return $lesson['id'];
                    }
                }
            }
        }

        return null;
    }

    /** @return array{id: string, status: string}|null */
    private function enrollmentForUser(EnrollmentRepository $enrollments, string $courseId, string $userId): ?array
    {
        $matches = $enrollments->all(CourseId::fromString($courseId), $userId);

        foreach ($matches as $enrollment) {
            if ($enrollment->status() !== EnrollmentStatus::Canceled) {
                return ['id' => $enrollment->id()->value(), 'status' => $enrollment->status()->value];
            }
        }

        return null;
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
