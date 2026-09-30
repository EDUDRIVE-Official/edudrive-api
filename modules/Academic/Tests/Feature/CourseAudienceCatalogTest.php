<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Application\Exceptions\CoursePedagogicalQualityRequired;
use Modules\Academic\Application\Services\CoursePublicationQualityGate;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Infrastructure\Services\CourseAudienceCatalog;
use Modules\Authorization\Domain\Enums\Role;

function audienceCourse(string $code, ?string $stage, bool $published = true): string
{
    $course = createDraftCourseForPublishing($code);
    if ($published) {
        approveCourseForPublishing($course);
        $course->publish(new DateTimeImmutable, completeCoverageForCourse($course));
        app(CourseRepository::class)->save($course);
    }
    DB::table('academic_lessons')->where('unit_id', $course->modules()[0]->units()[0]->id()->value())
        ->update(['learning_design' => $stage === null ? null : json_encode([
            'stage' => $stage, 'jurisdictions' => ['CR'], 'experience_type' => 'story',
            'behavior_objective' => 'Identificar un espacio protegido.',
            'competency_id' => '01981a64-8300-7b1d-b442-764ea7f915c0',
            'subcompetency_code' => 'CRUCE', 'indicator_codes' => ['OBSERVA'],
            'evidence_rules' => [['indicator_code' => 'OBSERVA', 'event_type' => 'decision', 'minimum_observations' => 1, 'weight' => 1]],
        ])]);

    return $course->id()->value();
}

it('does not recommend pending or unclassified courses', function (): void {
    $pending = audienceCourse('AUD-PENDING', 'pending_review');
    $missing = audienceCourse('AUD-MISSING', null);
    $this->actingAs(actingAsRole(Role::Student), 'web');
    $this->get('/courses')->assertOk()->assertDontSeeText('Recomendada para vos')
        ->assertSeeText('Público pendiente de revisión')->assertSeeText('Etapa por confirmar');
    $audiences = app(CourseAudienceCatalog::class)->forCourses([$pending, $missing]);
    expect($audiences[$pending]['stage'])->toBeNull()
        ->and($audiences[$missing]['classified'])->toBe(0);
});

it('blocks future publication of an experience with its audience pending', function (): void {
    $id = audienceCourse('EDU-EXP-AUD-PENDING', 'pending_review', false);
    try {
        app(CoursePublicationQualityGate::class)
            ->assertReady(CourseId::fromString($id));
        test()->fail('A pending audience was accepted.');
    } catch (CoursePedagogicalQualityRequired $exception) {
        expect($exception->getMessage())->toContain('público pendiente de revisión');
    }
});

it('recommends only a fully classified published course matching the learner', function (): void {
    $young = audienceCourse('AUD-YOUNG', 'discover');
    $adult = audienceCourse('AUD-ADULT', 'perfect');
    $draft = audienceCourse('AUD-DRAFT', 'discover', false);
    $student = actingAsRole(Role::Student);
    $student->forceFill(['date_of_birth' => now()->subYears(10)->toDateString()])->save();
    $response = $this->actingAs($student, 'web')->get('/courses')->assertOk();
    $courses = collect($response->viewData('courses'))->keyBy('id');
    expect($courses[$young]['is_recommended'])->toBeTrue()
        ->and($courses[$adult]['is_recommended'])->toBeFalse()
        ->and($courses[$draft]['is_recommended'])->toBeFalse();
});

it('does not infer a single audience from mixed or partially classified lessons', function (): void {
    $id = audienceCourse('AUD-MIXED', 'discover');
    $first = DB::table('academic_lessons')->first();
    $second = (array) $first;
    $second['id'] = (string) Str::uuid();
    $second['code'] = 'LEC-02';
    $second['position'] = 2;
    $second['learning_design'] = json_encode(['stage' => 'perfect']);
    DB::table('academic_lessons')->insert($second);
    $catalog = app(CourseAudienceCatalog::class);
    expect($catalog->forCourses([$id])[$id]['stage'])->toBeNull();
    DB::table('academic_lessons')->where('id', $second['id'])->update(['learning_design' => null]);
    expect($catalog->forCourses([$id])[$id]['classified'])->toBe(1)
        ->and($catalog->forCourses([$id])[$id]['stage'])->toBeNull();
});


it('does not recommend the 9-12 course outside its audience', function (int $age): void {
    $id = audienceCourse('AUD-BOUNDARY', 'discover');
    $student = actingAsRole(Role::Student);
    $student->forceFill(['date_of_birth' => now()->subYears($age)->toDateString()])->save();
    $response = $this->actingAs($student, 'web')->get('/courses')->assertOk();
    $courses = collect($response->viewData('courses'))->keyBy('id');
    expect($courses[$id]['is_recommended'])->toBeFalse();
})->with([3, 6, 7, 8, 13, 17, 35, 82]);
