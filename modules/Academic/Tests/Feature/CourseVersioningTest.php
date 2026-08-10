<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

use Tests\TestCase;

it('recorre revision, aprobacion, publicacion, reapertura y una segunda version', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-050');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/submit-for-review")
        ->assertOk()
        ->assertJsonPath('data.id', $course->id()->value())
        ->assertJsonPath('data.status', 'under_review');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    getJson("/api/v1/academic/courses/{$course->id()->value()}/versions")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.version_number', 1)
        ->assertJsonPath('data.0.status', 'published');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/reopen")
        ->assertOk()
        ->assertJsonPath('data.status', 'draft');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/submit-for-review")
        ->assertOk()
        ->assertJsonPath('data.status', 'under_review');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    $response = getJson("/api/v1/academic/courses/{$course->id()->value()}/versions")
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $versions = $response->json('data');
    expect($versions[0]['version_number'])->toBe(1)
        ->and($versions[0]['status'])->toBe('published')
        ->and($versions[1]['version_number'])->toBe(2)
        ->and($versions[1]['status'])->toBe('published');
});

it('envia un curso aprobado de vuelta a borrador', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-051');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/submit-for-review")
        ->assertOk()
        ->assertJsonPath('data.status', 'under_review');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/send-back-to-draft")
        ->assertOk()
        ->assertJsonPath('data.status', 'draft');
});

it('devuelve el snapshot completo de una version publicada', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-052');
    approveCourseThroughReviewFlow($this, $course->id()->value());
    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")->assertOk();

    $version = getJson("/api/v1/academic/courses/{$course->id()->value()}/versions/1")
        ->assertOk()
        ->json('data');

    expect($version['version_number'])->toBe(1)
        ->and($version['status'])->toBe('published')
        ->and($version['snapshot']['course']['id'])->toBe($course->id()->value())
        ->and($version['snapshot']['course']['code'])->toBe('EDU-052')
        ->and($version['snapshot']['modules'])->toHaveCount(1)
        ->and($version['snapshot']['modules'][0]['units'][0]['lessons'][0]['blocks'])->not->toBeEmpty();
});

it('rechaza aprobar un curso que no esta bajo revision', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-053');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/approve")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COURSE_REVIEW_STATE_INVALID');
});

it('rechaza enviar a revision un curso que no esta en borrador', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-054');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/submit-for-review")
        ->assertOk()
        ->assertJsonPath('data.status', 'under_review');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/submit-for-review")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COURSE_REVIEW_STATE_INVALID');
});

it('rechaza reabrir un curso que no esta publicado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-055');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/reopen")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COURSE_CANNOT_BE_REOPENED');
});

it('rechaza el ciclo de vida de un curso inexistente', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $courseId = (string) Str::uuid();

    foreach (['submit-for-review', 'approve', 'send-back-to-draft', 'reopen'] as $endpoint) {
        postJson("/api/v1/academic/courses/{$courseId}/{$endpoint}")
            ->assertNotFound()
            ->assertJsonPath('code', 'COURSE_NOT_FOUND');
    }
});

it('rechaza listar versiones de un curso inexistente', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    getJson('/api/v1/academic/courses/'.((string) Str::uuid()).'/versions')
        ->assertNotFound()
        ->assertJsonPath('code', 'COURSE_NOT_FOUND');
});

it('devuelve COURSE_VERSION_NOT_FOUND si el curso o la version no existen', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-056');

    getJson("/api/v1/academic/courses/{$course->id()->value()}/versions/1")
        ->assertNotFound()
        ->assertJsonPath('code', 'COURSE_VERSION_NOT_FOUND');

    getJson('/api/v1/academic/courses/'.((string) Str::uuid()).'/versions/1')
        ->assertNotFound()
        ->assertJsonPath('code', 'COURSE_VERSION_NOT_FOUND');
});

it('mantiene el borrador consultable tras publicar y reabrir', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-057');
    approveCourseThroughReviewFlow($this, $course->id()->value());
    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")->assertOk();
    postJson("/api/v1/academic/courses/{$course->id()->value()}/reopen")->assertOk();

    getJson("/api/v1/academic/courses/{$course->id()->value()}/curriculum")
        ->assertOk()
        ->assertJsonPath('data.id', $course->id()->value())
        ->assertJsonPath('data.status', 'draft');
});

it('protege el ciclo de vida y el historial con autenticacion', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('EDU-058');

    foreach (['submit-for-review', 'approve', 'send-back-to-draft', 'reopen', 'publish'] as $endpoint) {
        postJson("/api/v1/academic/courses/{$course->id()->value()}/{$endpoint}")
            ->assertUnauthorized();
    }

    getJson("/api/v1/academic/courses/{$course->id()->value()}/versions")
        ->assertUnauthorized();
    getJson("/api/v1/academic/courses/{$course->id()->value()}/versions/1")
        ->assertUnauthorized();
});

it('rechaza las mutaciones del ciclo de vida sin el permiso courses.manage', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('EDU-059');

    actingAsRole(Role::Student);

    postJson("/api/v1/academic/courses/{$course->id()->value()}/submit-for-review")
        ->assertForbidden();
    postJson("/api/v1/academic/courses/{$course->id()->value()}/approve")
        ->assertForbidden();
    postJson("/api/v1/academic/courses/{$course->id()->value()}/send-back-to-draft")
        ->assertForbidden();
    postJson("/api/v1/academic/courses/{$course->id()->value()}/reopen")
        ->assertForbidden();
});

it('permite consultar versiones con solo el permiso courses.view', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('EDU-060');

    actingAsSuperAdminUser();
    approveCourseThroughReviewFlow($this, $course->id()->value());
    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")->assertOk();

    actingAsRole(Role::Student);

    getJson("/api/v1/academic/courses/{$course->id()->value()}/versions")
        ->assertOk()
        ->assertJsonCount(1, 'data');
    getJson("/api/v1/academic/courses/{$course->id()->value()}/versions/1")
        ->assertOk()
        ->assertJsonPath('data.version_number', 1);
});
