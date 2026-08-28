<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use DateTimeInterface;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Gamification\Domain\Entities\ChallengeParticipation;
use Modules\Gamification\Domain\Entities\ExperienceEntry;
use Modules\Gamification\Domain\Entities\UserAchievement;
use Modules\Gamification\Domain\Entities\UserBadge;
use Modules\Gamification\Domain\Repositories\ChallengeParticipationRepository;
use Modules\Gamification\Domain\Repositories\ExperienceEntryRepository;
use Modules\Gamification\Domain\Repositories\UserAchievementRepository;
use Modules\Gamification\Domain\Repositories\UserBadgeRepository;
use Modules\Identity\Application\Responses\PersonalDataExportResponse;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Legal\Domain\Entities\UserConsent;
use Modules\Legal\Domain\Repositories\UserConsentRepository;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Repositories\NotificationPreferenceRepository;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Entities\DecisionPoint;
use Modules\Simulation\Domain\Entities\TelemetryEvent;
use Modules\Simulation\Domain\Repositories\DecisionPointRepository;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;

final readonly class ExportMyDataUseCase
{
    public function __construct(
        private UserRepository $users,
        private RoleAssignmentRepository $roleAssignments,
        private UserConsentRepository $consents,
        private EnrollmentRepository $enrollments,
        private ExamAttemptRepository $examAttempts,
        private CertificateRepository $certificates,
        private SimulationSessionRepository $simulationSessions,
        private TelemetryEventRepository $telemetryEvents,
        private DecisionPointRepository $decisionPoints,
        private RoadPassportRepository $roadPassports,
        private NotificationRepository $notifications,
        private NotificationPreferenceRepository $notificationPreferences,
        private UserBadgeRepository $userBadges,
        private UserAchievementRepository $userAchievements,
        private ChallengeParticipationRepository $challengeParticipations,
        private ExperienceEntryRepository $experienceEntries,
        private LearningEventRepository $learningEvents,
    ) {}

    public function execute(string $userId): PersonalDataExportResponse
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new UserNotFound;
        }

        $enrollments = $this->enrollments->all(userId: $userId);
        $roadPassport = $this->roadPassports->findByUserId($userId);
        $notificationPreference = $this->notificationPreferences->findByUserId($userId);

        return new PersonalDataExportResponse(
            profile: [
                'id' => $user->id(),
                'name' => $user->name(),
                'email' => $user->email()->value(),
                'status' => $user->status()->value,
                'created_at' => $user->createdAt()->format(DateTimeInterface::ATOM),
                'last_login_at' => $user->lastLoginAt()?->format(DateTimeInterface::ATOM),
            ],
            roleAssignments: array_map(
                static fn (RoleAssignment $assignment): array => [
                    'role' => $assignment->role()->value,
                    'organization_id' => $assignment->organizationId(),
                    'assigned_at' => $assignment->assignedAt()->format(DateTimeInterface::ATOM),
                ],
                $this->roleAssignments->findByUserId($userId),
            ),
            consents: array_map(
                static fn (UserConsent $consent): array => [
                    'policy_key' => $consent->policyKey()->value(),
                    'policy_version' => $consent->policyVersion(),
                    'accepted_at' => $consent->acceptedAt()->format(DateTimeInterface::ATOM),
                ],
                $this->consents->findByUserId($userId),
            ),
            enrollments: array_map(
                static fn (Enrollment $enrollment): array => [
                    'course_id' => $enrollment->courseId()->value(),
                    'status' => $enrollment->status()->value,
                    'source' => $enrollment->source()->value,
                    'enrolled_at' => $enrollment->enrolledAt()->format(DateTimeInterface::ATOM),
                ],
                $enrollments,
            ),
            examAttempts: array_map(
                static fn (ExamAttempt $attempt): array => [
                    'exam_id' => $attempt->examId()->value(),
                    'status' => $attempt->status()->value,
                    'started_at' => $attempt->startedAt()->format(DateTimeInterface::ATOM),
                    'submitted_at' => $attempt->submittedAt()?->format(DateTimeInterface::ATOM),
                    'score' => $attempt->score(),
                    'total_points' => $attempt->totalPoints(),
                    'percentage' => $attempt->percentage(),
                    'passed' => $attempt->passed(),
                ],
                $this->examAttempts->all(userId: $userId),
            ),
            certificates: array_map(
                static fn (Certificate $certificate): array => [
                    'course_id' => $certificate->courseId(),
                    'validation_code' => $certificate->validationCode()->value(),
                    'status' => $certificate->status()->value,
                    'issued_at' => $certificate->issuedAt()->format(DateTimeInterface::ATOM),
                    'expires_at' => $certificate->expiresAt()?->format(DateTimeInterface::ATOM),
                ],
                $this->certificates->allForUser($userId),
            ),
            simulationSessions: array_map(
                function (SimulationSession $session): array {
                    $sessionId = $session->id()->value();

                    return [
                        'simulator_id' => $session->simulatorId(),
                        'vehicle_type' => $session->vehicleType(),
                        'scenario' => $session->scenario(),
                        'status' => $session->status()->value,
                        'scheduled_at' => $session->scheduledAt()->format(DateTimeInterface::ATOM),
                        'started_at' => $session->startedAt()?->format(DateTimeInterface::ATOM),
                        'ended_at' => $session->endedAt()?->format(DateTimeInterface::ATOM),
                        'telemetry_events' => array_map(
                            static fn (TelemetryEvent $event): array => [
                                'type' => $event->type()->value,
                                'details' => $event->details(),
                                'occurred_at' => $event->occurredAt()->format(DateTimeInterface::ATOM),
                            ],
                            $this->telemetryEvents->allForSession($sessionId),
                        ),
                        'decision_points' => array_map(
                            static fn (DecisionPoint $point): array => [
                                'road_context' => $point->roadContext(),
                                'risk_level' => $point->riskLevel()->value,
                                'driver_reaction' => $point->driverReaction()->value,
                                'occurred_at' => $point->occurredAt()->format(DateTimeInterface::ATOM),
                            ],
                            $this->decisionPoints->allForSession($sessionId),
                        ),
                    ];
                },
                $this->simulationSessions->allForUser($userId),
            ),
            roadPassport: $roadPassport === null ? null : [
                'status' => $roadPassport->status()->value,
                'level' => $roadPassport->level(),
                'issued_at' => $roadPassport->issuedAt()->format(DateTimeInterface::ATOM),
            ],
            notifications: array_map(
                static fn (Notification $notification): array => [
                    'channel' => $notification->channel()->value,
                    'category' => $notification->category(),
                    'subject' => $notification->subject(),
                    'status' => $notification->status()->value,
                    'sent_at' => $notification->sentAt()->format(DateTimeInterface::ATOM),
                    'read_at' => $notification->readAt()?->format(DateTimeInterface::ATOM),
                ],
                $this->notifications->allForUser($userId),
            ),
            notificationPreferences: $notificationPreference === null ? null : [
                'allowed_channels' => array_map(
                    static fn ($channel): string => $channel->value,
                    $notificationPreference->allowedChannels(),
                ),
                'muted_categories' => $notificationPreference->mutedCategories(),
                'frequency' => $notificationPreference->frequency()->value,
                'consent_given' => $notificationPreference->consentGiven(),
                'consent_updated_at' => $notificationPreference->consentUpdatedAt()?->format(DateTimeInterface::ATOM),
            ],
            badges: array_map(
                static fn (UserBadge $badge): array => [
                    'badge_id' => $badge->badgeId(),
                    'earned_at' => $badge->earnedAt()->format(DateTimeInterface::ATOM),
                ],
                $this->userBadges->allForUser($userId),
            ),
            achievements: array_map(
                static fn (UserAchievement $achievement): array => [
                    'achievement_id' => $achievement->achievementId(),
                    'earned_at' => $achievement->earnedAt()->format(DateTimeInterface::ATOM),
                ],
                $this->userAchievements->allForUser($userId),
            ),
            challengeParticipations: array_map(
                static fn (ChallengeParticipation $participation): array => [
                    'challenge_id' => $participation->challengeId(),
                    'status' => $participation->status()->value,
                    'joined_at' => $participation->joinedAt()->format(DateTimeInterface::ATOM),
                    'completed_at' => $participation->completedAt()?->format(DateTimeInterface::ATOM),
                ],
                $this->challengeParticipations->allForUser($userId),
            ),
            experienceEntries: array_map(
                static fn (ExperienceEntry $entry): array => [
                    'points' => $entry->points(),
                    'reason' => $entry->reason(),
                    'recorded_at' => $entry->recordedAt()->format(DateTimeInterface::ATOM),
                ],
                $this->experienceEntries->allForUser($userId),
            ),
            learningEvents: array_merge([], ...array_map(
                fn (Enrollment $enrollment): array => array_map(
                    static fn (LearningEvent $event): array => [
                        'course_id' => $event->courseId(),
                        'verb' => $event->verb()->value,
                        'subject_id' => $event->subjectId(),
                        'occurred_at' => $event->occurredAt()->format(DateTimeInterface::ATOM),
                    ],
                    $this->learningEvents->findByEnrollmentId($enrollment->id()->value()),
                ),
                $enrollments,
            )),
        );
    }
}
