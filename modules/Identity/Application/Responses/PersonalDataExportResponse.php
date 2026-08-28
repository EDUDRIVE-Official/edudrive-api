<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Responses;

final readonly class PersonalDataExportResponse
{
    /**
     * @param  array<string, mixed>  $profile
     * @param  list<array<string, mixed>>  $roleAssignments
     * @param  list<array<string, mixed>>  $consents
     * @param  list<array<string, mixed>>  $enrollments
     * @param  list<array<string, mixed>>  $examAttempts
     * @param  list<array<string, mixed>>  $certificates
     * @param  list<array<string, mixed>>  $simulationSessions
     * @param  ?array<string, mixed>  $roadPassport
     * @param  list<array<string, mixed>>  $notifications
     * @param  ?array<string, mixed>  $notificationPreferences
     * @param  list<array<string, mixed>>  $badges
     * @param  list<array<string, mixed>>  $achievements
     * @param  list<array<string, mixed>>  $challengeParticipations
     * @param  list<array<string, mixed>>  $experienceEntries
     * @param  list<array<string, mixed>>  $learningEvents
     */
    public function __construct(
        public array $profile,
        public array $roleAssignments,
        public array $consents,
        public array $enrollments,
        public array $examAttempts,
        public array $certificates,
        public array $simulationSessions,
        public ?array $roadPassport,
        public array $notifications,
        public ?array $notificationPreferences,
        public array $badges,
        public array $achievements,
        public array $challengeParticipations,
        public array $experienceEntries,
        public array $learningEvents,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'profile' => $this->profile,
            'role_assignments' => $this->roleAssignments,
            'consents' => $this->consents,
            'enrollments' => $this->enrollments,
            'exam_attempts' => $this->examAttempts,
            'certificates' => $this->certificates,
            'simulation_sessions' => $this->simulationSessions,
            'road_passport' => $this->roadPassport,
            'notifications' => $this->notifications,
            'notification_preferences' => $this->notificationPreferences,
            'badges' => $this->badges,
            'achievements' => $this->achievements,
            'challenge_participations' => $this->challengeParticipations,
            'experience_entries' => $this->experienceEntries,
            'learning_events' => $this->learningEvents,
        ];
    }
}
