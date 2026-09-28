<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Exceptions\LessonNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentProgressQuery;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Identity\Application\Commands\RecordGuardianPracticeObservationCommand;
use Modules\Identity\Application\Exceptions\GuardianRelationshipNotFound;
use Modules\Identity\Application\Queries\GetLinkedMinorProgressQuery;
use Modules\Identity\Application\Queries\ListMyLinkedMinorsQuery;
use Modules\Identity\Application\UseCases\GetLinkedMinorProgressHandler;
use Modules\Identity\Application\UseCases\ListMyLinkedMinorsHandler;
use Modules\Identity\Application\UseCases\RecordGuardianPracticeObservationHandler;
use Modules\RoadPassport\Domain\Enums\EvidenceType;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;

final class GuardianWebController extends Controller
{
    public function __construct(
        private readonly ListMyLinkedMinorsHandler $listMinors,
        private readonly GetLinkedMinorProgressHandler $minorProgress,
        private readonly CourseRepository $courses,
        private readonly CourseLessonCatalog $lessons,
        private readonly RecordGuardianPracticeObservationHandler $recordObservation,
        private readonly RoadPassportRepository $passports,
    ) {}

    public function index(Request $request, QueryBus $queryBus): View
    {
        $guardianUserId = (string) $request->user()?->getAuthIdentifier();
        $minors = $this->listMinors->handle(new ListMyLinkedMinorsQuery(
            guardianUserId: $guardianUserId,
        ));

        $minorCards = [];
        $coursesById = [];
        foreach ($this->courses->all() as $course) {
            $coursesById[$course->id()->value()] = $course;
        }

        foreach ($minors as $minor) {
            $card = $minor->toArray();
            $profile = $this->minorProgress->handle(new GetLinkedMinorProgressQuery($guardianUserId, $card['user_id']))->toArray();
            $completedLessonIds = [];
            foreach ($profile['enrollments'] as $enrollment) {
                $course = $coursesById[$enrollment['course_id']] ?? null;
                if ($course === null) {
                    continue;
                }

                $progress = $queryBus->ask(new GetEnrollmentProgressQuery(
                    enrollmentId: $enrollment['enrollment_id'], userId: $card['user_id'], canViewOthers: true,
                ));
                assert($progress instanceof EnrollmentProgressResponse);
                foreach ($progress->completedLessons as $lessonId) {
                    $lesson = $this->lessons->lessonFor($course, LessonId::fromString($lessonId));
                    if ($lesson?->learningDesign()?->requiresGuardian === true) {
                        $completedLessonIds[$lessonId] = true;
                    }
                }
            }
            $observedLessonIds = [];
            foreach ($this->passports->findByUserId($card['user_id'])?->evidence() ?? [] as $evidence) {
                $lessonId = $evidence->details['lesson_id'] ?? null;
                if ($evidence->type === EvidenceType::GuidedPracticeObserved
                    && is_string($lessonId)
                    && $evidence->subjectId === hash('sha256', $guardianUserId.'|'.$lessonId)) {
                    $observedLessonIds[$lessonId] = true;
                }
            }
            $card['pending_observations_count'] = count(array_diff_key($completedLessonIds, $observedLessonIds));
            $card['recorded_observations_count'] = count($observedLessonIds);
            $minorCards[] = $card;
        }

        usort($minorCards, static fn (array $left, array $right): int => $right['pending_observations_count'] <=> $left['pending_observations_count']);

        return view('guardians.index', [
            'minors' => $minorCards,
        ]);
    }

    public function show(Request $request, string $minorUserId, QueryBus $queryBus): View
    {
        try {
            $profile = $this->minorProgress->handle(new GetLinkedMinorProgressQuery(
                guardianUserId: (string) $request->user()?->getAuthIdentifier(),
                minorUserId: $minorUserId,
            ))->toArray();
        } catch (GuardianRelationshipNotFound) {
            abort(404);
        }

        $courseNames = [];
        foreach ($this->courses->all() as $course) {
            $courseNames[$course->id()->value()] = $course->title()->value();
        }
        $coursesById = [];
        foreach ($this->courses->all() as $course) {
            $coursesById[$course->id()->value()] = $course;
        }
        $observations = [];
        $passport = $this->passports->findByUserId($minorUserId);
        $guardianUserId = (string) $request->user()?->getAuthIdentifier();
        $reflections = [];
        foreach ($passport?->evidence() ?? [] as $item) {
            if ($item->type !== EvidenceType::StudentReflection) {
                continue;
            }
            $observationSubjectId = $item->details['observation_subject_id'] ?? null;
            if (is_string($observationSubjectId)) {
                $reflections[$observationSubjectId] = [
                    'learned' => (string) ($item->details['learned'] ?? ''),
                    'next_action' => (string) ($item->details['next_action'] ?? ''),
                    'occurred_at' => $item->occurredAt->format('d/m/Y'),
                ];
            }
        }
        foreach ($passport?->evidence() ?? [] as $item) {
            if ($item->type !== EvidenceType::GuidedPracticeObserved) {
                continue;
            }
            $lessonId = $item->details['lesson_id'] ?? null;
            if (! is_string($lessonId) || $item->subjectId !== hash('sha256', $guardianUserId.'|'.$lessonId)) {
                continue;
            }
            $observations[$lessonId] = [
                'behavior' => (string) ($item->details['behavior'] ?? ''),
                'practice_context' => (string) ($item->details['practice_context'] ?? ''),
                'note' => is_string($item->details['observation'] ?? null) ? $item->details['observation'] : null,
                'occurred_at' => $item->occurredAt->format('d/m/Y'),
                'reflection' => $reflections[$item->subjectId] ?? null,
            ];
        }
        $profile['enrollments'] = array_map(function (array $enrollment) use ($courseNames, $coursesById, $observations, $queryBus, $minorUserId): array {
            $progress = $queryBus->ask(new GetEnrollmentProgressQuery(
                enrollmentId: $enrollment['enrollment_id'],
                userId: $minorUserId,
                canViewOthers: true,
            ));
            assert($progress instanceof EnrollmentProgressResponse);

            $completedLessons = [];
            $course = $coursesById[$enrollment['course_id']] ?? null;
            if ($course !== null) {
                foreach ($progress->completedLessons as $lessonId) {
                    $lesson = $this->lessons->lessonFor($course, LessonId::fromString($lessonId));
                    if ($lesson?->learningDesign()?->requiresGuardian === true) {
                        $completedLessons[] = [
                            'id' => $lessonId,
                            'title' => $lesson->title(),
                            'observation' => $observations[$lessonId] ?? null,
                        ];
                    }
                }
            }

            return array_merge($enrollment, [
                'course_title' => $courseNames[$enrollment['course_id']] ?? 'Curso de educación vial',
                'progress' => $progress->toArray(),
                'completed_lesson_options' => $completedLessons,
            ]);
        }, $profile['enrollments']);

        $profile['pending_observations_count'] = 0;
        $profile['recorded_observations_count'] = 0;
        $profile['next_pending_practice'] = null;
        $requestedLessonId = (string) $request->query('lesson_id', '');
        $requestedLessonId = Str::isUuid($requestedLessonId) ? $requestedLessonId : null;
        $pendingPractices = [];
        foreach ($profile['enrollments'] as $enrollment) {
            foreach ($enrollment['completed_lesson_options'] as $lesson) {
                if ($lesson['observation'] === null) {
                    $profile['pending_observations_count']++;
                    $pendingPractices[] = [
                        'enrollment_id' => $enrollment['enrollment_id'],
                        'lesson_id' => $lesson['id'],
                        'course_title' => $enrollment['course_title'],
                        'lesson_title' => $lesson['title'],
                    ];
                } else {
                    $profile['recorded_observations_count']++;
                }
            }
        }

        $profile['next_pending_practice'] = collect($pendingPractices)
            ->firstWhere('lesson_id', $requestedLessonId)
            ?? ($pendingPractices[0] ?? null);
        $profile['selected_lesson_id'] = $profile['next_pending_practice']['lesson_id'] ?? null;

        return view('guardians.show', ['profile' => $profile]);
    }

    public function storeObservation(Request $request, string $minorUserId): RedirectResponse
    {
        $data = $request->validate([
            'enrollment_id' => ['required', 'uuid'],
            'lesson_id' => ['required', 'uuid'],
            'behavior' => ['required', 'in:identified_risk,made_safe_decision,applied_safe_sequence'],
            'practice_context' => ['required', 'in:school_route,neighborhood,controlled_space,tabletop_model'],
            'note' => ['required', 'string', 'min:10', 'max:500'],
            'safe_environment' => ['accepted'],
        ], [
            'practice_context.required' => 'Indicá dónde se realizó la práctica.',
            'note.required' => 'Describí brevemente qué observaste durante la práctica.',
            'note.min' => 'La observación debe tener al menos 10 caracteres.',
            'safe_environment.accepted' => 'Debés confirmar que la práctica ocurrió en un entorno seguro y supervisado.',
        ]);

        try {
            $this->recordObservation->handle(new RecordGuardianPracticeObservationCommand(
                guardianUserId: (string) $request->user()?->getAuthIdentifier(),
                minorUserId: $minorUserId,
                enrollmentId: $data['enrollment_id'],
                lessonId: $data['lesson_id'],
                behavior: $data['behavior'],
                practiceContext: $data['practice_context'],
                note: $data['note'],
            ));
        } catch (GuardianRelationshipNotFound|EnrollmentNotFound|LessonNotFound) {
            abort(404);
        }

        return redirect()->route('guardians.web.show', $minorUserId)
            ->with('success', 'La práctica acompañada quedó registrada en el Pasaporte Vial.');
    }
}
