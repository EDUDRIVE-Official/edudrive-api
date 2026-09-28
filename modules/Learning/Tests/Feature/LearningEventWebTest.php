<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedLearningWebUser(string $name = 'Estudiante de actividad'): User
{
    $user = User::register(id: (string) Str::uuid(), name: $name, email: Email::fromString(Str::uuid().'@edudrive.cr'), passwordHash: 'hash');
    app(UserRepository::class)->save($user);

    return $user;
}

function persistedLearningWebEnrollment(string $userId): Enrollment
{
    $course = createDraftCourseForPublishing('WEB-LRN-'.strtoupper(Str::random(5)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $userId,
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('requiere autenticación para consultar actividad académica', function (): void {
    /** @var TestCase $this */
    $this->get('/matriculas/'.Str::uuid().'/actividad')->assertRedirect(route('login'));
});

it('muestra los eventos de una matrícula propia', function (): void {
    /** @var TestCase $this */
    $user = persistedLearningWebUser();
    $enrollment = persistedLearningWebEnrollment($user->id());
    app(LearningEventRepository::class)->record(LearningEvent::create(
        id: LearningEventId::fromString((string) Str::uuid()),
        enrollmentId: $enrollment->id()->value(),
        userId: $user->id(),
        courseId: $enrollment->courseId()->value(),
        verb: LearningVerb::LessonCompleted,
        subjectId: (string) Str::uuid(),
        occurredAt: new DateTimeImmutable('now'),
        evidence: [
            'score' => 95,
            'reflection' => 'Voy a detenerme y observar con calma antes de cruzar.',
            'self_assessment' => 'voy_avanzando',
        ],
    ));
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/matriculas/'.$enrollment->id()->value().'/actividad')
        ->assertOk()
        ->assertSeeText('Lección completada')
        ->assertSeeText('95')
        ->assertSeeText('Mi cierre de la lección')
        ->assertSeeText('Voy avanzando')
        ->assertSeeText('Voy a detenerme y observar con calma antes de cruzar.');
});

it('oculta las matrículas ajenas como no encontradas', function (): void {
    /** @var TestCase $this */
    $owner = persistedLearningWebUser('Propietario');
    $enrollment = persistedLearningWebEnrollment($owner->id());
    $viewer = persistedLearningWebUser('Visitante');
    $this->actingAs(UserModel::query()->findOrFail($viewer->id()), 'web');

    $this->get('/matriculas/'.$enrollment->id()->value().'/actividad')->assertNotFound();
});
