<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedExternalEnrollmentUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de integracion externa',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('crea matriculas institucionales masivas con un consumidor autorizado con el alcance enrollments.manage', function (): void {
    /** @var TestCase $this */
    [, $token] = persistedApiConsumerFeature(['enrollments.manage']);
    $course = createDraftCourseForPublishing('EXT-'.strtoupper((string) Str::random(4)));
    $organizationId = (string) Str::uuid();

    $this->withToken($token)->postJson('/api/v1/external/institutional/enrollments', [
        'course_id' => $course->id()->value(),
        'organization_id' => $organizationId,
        'user_ids' => [persistedExternalEnrollmentUserId(), persistedExternalEnrollmentUserId()],
    ])
        ->assertCreated()
        ->assertJsonPath('data.total', 2)
        ->assertJsonPath('data.created', 2)
        ->assertJsonPath('data.failed', 0);
});

it('rechaza el acceso a la matricula institucional externa sin el alcance enrollments.manage', function (): void {
    /** @var TestCase $this */
    [, $token] = persistedApiConsumerFeature(['reports.view']);
    $course = createDraftCourseForPublishing('EXT-'.strtoupper((string) Str::random(4)));

    $this->withToken($token)->postJson('/api/v1/external/institutional/enrollments', [
        'course_id' => $course->id()->value(),
        'organization_id' => (string) Str::uuid(),
        'user_ids' => [(string) Str::uuid()],
    ])->assertForbidden();
});

it('rechaza el acceso a la matricula institucional externa sin una llave de integracion valida', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('EXT-'.strtoupper((string) Str::random(4)));

    $this->postJson('/api/v1/external/institutional/enrollments', [
        'course_id' => $course->id()->value(),
        'organization_id' => (string) Str::uuid(),
        'user_ids' => [(string) Str::uuid()],
    ])->assertUnauthorized();
});

it('valida los campos requeridos de la matricula institucional externa', function (): void {
    /** @var TestCase $this */
    [, $token] = persistedApiConsumerFeature(['enrollments.manage']);

    $this->withToken($token)->postJson('/api/v1/external/institutional/enrollments', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['course_id', 'organization_id', 'user_ids']);
});

it('responde 404 cuando el curso no existe', function (): void {
    /** @var TestCase $this */
    [, $token] = persistedApiConsumerFeature(['enrollments.manage']);

    $this->withToken($token)->postJson('/api/v1/external/institutional/enrollments', [
        'course_id' => (string) Str::uuid(),
        'organization_id' => (string) Str::uuid(),
        'user_ids' => [(string) Str::uuid()],
    ])
        ->assertStatus(404)
        ->assertJsonPath('code', 'COURSE_NOT_FOUND');
});
