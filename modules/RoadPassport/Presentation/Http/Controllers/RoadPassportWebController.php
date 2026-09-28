<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Notification\Application\Services\StudentReflectionNotifier;
use Modules\RoadPassport\Application\DTO\EvidenceEntry;
use Modules\RoadPassport\Application\Exceptions\RoadPassportNotFound;
use Modules\RoadPassport\Application\Queries\GetMyRoadPassportQuery;
use Modules\RoadPassport\Application\Responses\RoadPassportResponse;
use Modules\RoadPassport\Application\Services\RoadPassportEvidenceRecorder;
use Modules\RoadPassport\Application\Services\RoadPassportQrCode;
use Modules\RoadPassport\Application\Services\RoadPassportVerificationCode;
use Modules\RoadPassport\Domain\Enums\EvidenceType;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;

final class RoadPassportWebController
{
    public function show(
        Request $request,
        QueryBus $queryBus,
        CourseRepository $courses,
        CompetencyRepository $competencies,
        GuardianRelationshipRepository $relationships,
        UserRepository $users,
        RoadPassportVerificationCode $verificationCodes,
        RoadPassportQrCode $qrCodes,
    ): View {
        $userId = (string) $request->user()?->getAuthIdentifier();

        try {
            $result = $queryBus->ask(new GetMyRoadPassportQuery(userId: $userId));
            assert($result instanceof RoadPassportResponse);
            $passport = $result->toArray();
            $courseCatalog = [];
            foreach ($courses->all() as $course) {
                $courseCatalog[$course->id()->value()] = $course->title()->value();
            }

            $competencyCatalog = [];
            $indicatorCatalog = [];
            foreach ($competencies->all() as $competency) {
                $competencyCatalog[$competency->id()->value()] = $competency->title();
                foreach ($competency->subcompetencies() as $subcompetency) {
                    foreach ($subcompetency->indicators() as $indicator) {
                        $indicatorCatalog[$indicator->code()] = $indicator->description();
                    }
                }
            }

            $passport['issued_at_label'] = $this->formatDateForCostaRica($passport['issued_at']);
            $passport['history'] = array_map(
                fn (array $entry): array => array_merge($entry, [
                    'occurred_at_label' => $this->formatDateForCostaRica($entry['occurred_at']),
                ]),
                $passport['history'],
            );
            $passport['evidence'] = array_map(
                function (array $item) use ($courseCatalog, $competencyCatalog, $indicatorCatalog, $relationships, $users): array {
                    $details = $item['details'];
                    $indicatorCodes = is_array($details['indicator_codes'] ?? null) ? $details['indicator_codes'] : [];
                    $observerName = null;
                    if ($item['type'] === EvidenceType::GuidedPracticeObserved->value
                        && is_string($details['guardian_relationship_id'] ?? null)) {
                        $relationship = $relationships->findById($details['guardian_relationship_id']);
                        $observerName = $relationship === null ? null : $users->findById($relationship->guardianUserId())?->name();
                    }

                    return array_merge($item, [
                        'course_title' => $courseCatalog[$item['course_id']] ?? 'Actividad de educación vial',
                        'competency_title' => isset($details['competency_id'])
                            ? ($competencyCatalog[$details['competency_id']] ?? null)
                            : null,
                        'indicator_labels' => array_values(array_map(
                            static fn (string $code): string => $indicatorCatalog[$code] ?? $code,
                            $indicatorCodes,
                        )),
                        'observer_name' => $observerName,
                        'occurred_at_label' => $this->formatDateForCostaRica($item['occurred_at']),
                    ]);
                },
                $passport['evidence'],
            );
        } catch (RoadPassportNotFound) {
            $passport = null;
        }

        $authenticatedUser = $request->user();
        $selfPracticeRestriction = match (true) {
            ! $authenticatedUser instanceof UserModel || $authenticatedUser->date_of_birth === null => 'birth_date_required',
            $authenticatedUser->date_of_birth->age < 18 => 'adult_accompaniment_required',
            default => null,
        };

        $verificationCode = $passport === null ? null : $verificationCodes->issue($passport['id'], (int) $passport['verification_version']);
        $verificationUrl = $verificationCode === null ? null : route('road-passport.verify', ['code' => $verificationCode]);
        $requestedObservation = (string) $request->query('observation', '');
        $selectedObservationSubjectId = null;
        if ($passport !== null && preg_match('/^[a-f0-9]{64}$/', $requestedObservation) === 1) {
            $selectedObservationSubjectId = collect($passport['evidence'])->contains(
                static fn (array $item): bool => $item['type'] === EvidenceType::GuidedPracticeObserved->value
                    && $item['subject_id'] === $requestedObservation,
            ) ? $requestedObservation : null;
        }

        return view('road-passport.show', [
            'passport' => $passport,
            'verificationCode' => $verificationCode,
            'verificationUrl' => $verificationUrl,
            'verificationQrDataUri' => $verificationUrl === null ? null : $qrCodes->dataUri($verificationUrl),
            'canSelfReportPractice' => $selfPracticeRestriction === null,
            'selfPracticeRestriction' => $selfPracticeRestriction,
            'selectedObservationSubjectId' => $selectedObservationSubjectId,
        ]);
    }

    public function storeReflection(
        Request $request,
        RoadPassportRepository $passports,
        RoadPassportEvidenceRecorder $evidence,
        AuditLogger $audit,
        GuardianRelationshipRepository $relationships,
        UserRepository $users,
        StudentReflectionNotifier $notifier,
    ): RedirectResponse {
        $data = $request->validate([
            'observation_subject_id' => ['required', 'string', 'size:64'],
            'learned' => ['required', 'string', 'min:10', 'max:500'],
            'next_action' => ['required', 'string', 'min:10', 'max:500'],
        ]);
        $userId = (string) $request->user()?->getAuthIdentifier();
        $passport = $passports->findByUserId($userId);
        if ($passport === null) {
            abort(404);
        }

        $observation = null;
        foreach ($passport->evidence() as $item) {
            if ($item->type === EvidenceType::GuidedPracticeObserved && $item->subjectId === $data['observation_subject_id']) {
                $observation = $item;
                break;
            }
        }
        if ($observation === null) {
            abort(404);
        }

        $evidence->record(new EvidenceEntry(
            userId: $userId,
            type: EvidenceType::StudentReflection,
            subjectId: hash('sha256', 'reflection|'.$observation->subjectId),
            courseId: $observation->courseId,
            details: [
                'observation_subject_id' => $observation->subjectId,
                'lesson_title' => $observation->details['lesson_title'] ?? 'Práctica acompañada',
                'learned' => $data['learned'],
                'next_action' => $data['next_action'],
            ],
        ));
        $audit->log(new AuditEntry(
            action: 'road_passport.student_reflection_recorded',
            userId: $userId,
            entity: 'RoadPassport',
            entityId: $passport->id()->value(),
            metadata: ['course_id' => $observation->courseId],
        ));

        $relationshipId = $observation->details['guardian_relationship_id'] ?? null;
        if (is_string($relationshipId)) {
            $relationship = $relationships->findById($relationshipId);
            $student = $users->findById($userId);
            if ($relationship?->isActive() === true && $relationship->minorUserId() === $userId && $student !== null) {
                $notifier->notify(
                    $relationship->guardianUserId(),
                    $student->name(),
                    (string) ($observation->details['lesson_title'] ?? 'una práctica acompañada'),
                );
            }
        }

        return redirect()->route('road-passport.show')->with('status', 'Tu reflexión quedó guardada en el Pasaporte Vial.');
    }

    public function storePersonalPractice(
        Request $request,
        RoadPassportRepository $passports,
        RoadPassportEvidenceRecorder $evidence,
        AuditLogger $audit,
    ): RedirectResponse {
        $data = $request->validate([
            'lesson_id' => ['required', 'uuid'],
            'behavior' => ['required', 'in:identified_risk,made_safe_decision,applied_safe_sequence'],
            'practice_context' => ['required', 'in:controlled_space,school_route,neighborhood,tabletop_model'],
            'note' => ['required', 'string', 'min:10', 'max:500'],
            'safe_environment' => ['accepted'],
        ]);
        $authenticatedUser = $request->user();
        if (! $authenticatedUser instanceof UserModel
            || $authenticatedUser->date_of_birth === null
            || $authenticatedUser->date_of_birth->age < 18) {
            abort(403);
        }

        $userId = (string) $request->user()?->getAuthIdentifier();
        $passport = $passports->findByUserId($userId);
        if ($passport === null) {
            abort(404);
        }

        $lessonEvidence = null;
        foreach ($passport->evidence() as $item) {
            if ($item->type === EvidenceType::LessonCompleted && $item->subjectId === $data['lesson_id']) {
                $lessonEvidence = $item;
                break;
            }
        }
        if ($lessonEvidence === null) {
            abort(404);
        }

        $evidence->record(new EvidenceEntry(
            userId: $userId,
            type: EvidenceType::SelfReportedPractice,
            subjectId: hash('sha256', 'self-practice|'.$userId.'|'.$data['lesson_id']),
            courseId: $lessonEvidence->courseId,
            details: [
                'lesson_id' => $data['lesson_id'],
                'lesson_title' => $lessonEvidence->details['lesson_title'] ?? 'Práctica personal',
                'behavior' => $data['behavior'],
                'practice_context' => $data['practice_context'],
                'observation' => $data['note'],
                'observer_role' => 'self',
                'externally_verified' => false,
                'safe_environment_confirmed' => true,
                'competency_id' => $lessonEvidence->details['competency_id'] ?? null,
                'indicator_codes' => $lessonEvidence->details['indicator_codes'] ?? [],
            ],
        ));
        $audit->log(new AuditEntry(
            action: 'road_passport.personal_practice_recorded',
            userId: $userId,
            entity: 'RoadPassport',
            entityId: $passport->id()->value(),
            metadata: ['lesson_id' => $data['lesson_id'], 'externally_verified' => false],
        ));

        return redirect()->route('road-passport.show')->with('status', 'Tu práctica personal quedó registrada como evidencia declarada, no verificada externamente.');
    }

    public function rotateVerificationLink(
        Request $request,
        RoadPassportRepository $passports,
        AuditLogger $audit,
    ): RedirectResponse {
        $userId = (string) $request->user()?->getAuthIdentifier();
        $passport = $passports->findByUserId($userId);
        if ($passport === null) {
            abort(404);
        }

        $previousVersion = $passport->verificationVersion();
        $passport->rotateVerificationLink();
        $passports->save($passport);
        $audit->log(new AuditEntry(
            action: 'road_passport.verification_link_rotated',
            userId: $userId,
            entity: 'RoadPassport',
            entityId: $passport->id()->value(),
            metadata: [
                'previous_version' => $previousVersion,
                'new_version' => $passport->verificationVersion(),
            ],
        ));

        return redirect()->route('road-passport.show')
            ->with('status', 'Creamos un nuevo enlace de verificación. Los enlaces y códigos QR anteriores dejaron de funcionar.');
    }

    private function formatDateForCostaRica(string $date): string
    {
        return Carbon::parse($date)
            ->timezone((string) config('app.timezone', 'America/Costa_Rica'))
            ->settings(['locale' => 'es'])
            ->translatedFormat('j \\d\\e F \\d\\e Y, g:i a');
    }
}
