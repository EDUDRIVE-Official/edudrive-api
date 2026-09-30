<?php

declare(strict_types=1);

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Application\Exceptions\CoursePedagogicalQualityRequired;
use Modules\Academic\Application\Services\CoursePublicationQualityGate;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function assertUnreviewedDemoRemainsDraft(): void
{
    $course = DB::table('academic_courses')->where('code', 'EDU-EXP-001')->first();
    expect($course)->not->toBeNull()
        ->and($course->status)->toBe('draft')
        ->and($course->published_at)->toBeNull();
    expect(fn () => app(CoursePublicationQualityGate::class)->assertReady(CourseId::fromString($course->id)))
        ->toThrow(CoursePedagogicalQualityRequired::class);
    // A blocked first course must not prevent the remaining seeders from running.
    expect(DB::table('academic_courses')->where('code', 'EDU-EXP-002')->exists())->toBeTrue();
}

it('crea la cuenta de prueba en el ambiente local', function (): void {
    app()['env'] = 'local';

    (new DatabaseSeeder)->run();

    expect(app(UserRepository::class)->existsByEmail(Email::fromString('test@example.com')))->toBeTrue();
    assertUnreviewedDemoRemainsDraft();
});

it('crea la cuenta de prueba en el ambiente testing', function (): void {
    app()['env'] = 'testing';

    (new DatabaseSeeder)->run();

    expect(app(UserRepository::class)->existsByEmail(Email::fromString('test@example.com')))->toBeTrue();
    assertUnreviewedDemoRemainsDraft();
});

it('no crea la cuenta de prueba en produccion', function (): void {
    app()['env'] = 'production';

    (new DatabaseSeeder)->run();

    expect(app(UserRepository::class)->existsByEmail(Email::fromString('test@example.com')))->toBeFalse();
});

it('no crea la cuenta de prueba en staging', function (): void {
    app()['env'] = 'staging';

    (new DatabaseSeeder)->run();

    expect(app(UserRepository::class)->existsByEmail(Email::fromString('test@example.com')))->toBeFalse();
});

it('no duplica la cuenta de prueba si ya existe', function (): void {
    app()['env'] = 'local';

    (new DatabaseSeeder)->run();
    $courseCount = DB::table('academic_courses')->count();
    (new DatabaseSeeder)->run();

    expect(app(UserRepository::class)->existsByEmail(Email::fromString('test@example.com')))->toBeTrue();
    expect(DB::table('academic_courses')->count())->toBe($courseCount);
    assertUnreviewedDemoRemainsDraft();
});
