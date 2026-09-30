<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Application\Commands\ApproveCourseCommand;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Commands\ReopenCourseCommand;
use Modules\Academic\Application\Commands\SubmitCourseForReviewCommand;
use Modules\Academic\Application\UseCases\ApproveCourseHandler;
use Modules\Academic\Application\UseCases\PublishCourseHandler;
use Modules\Academic\Application\UseCases\ReopenCourseHandler;
use Modules\Academic\Application\UseCases\SubmitCourseForReviewHandler;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\LessonLearningDesign;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$apply = in_array('--apply-approved-courses', $argv, true);
if (! $apply && (! app()->environment('testing') || DB::connection()->getDatabaseName() !== 'edudrive_editorial_rehearsal')) {
    throw new RuntimeException('Dry rehearsal requires the isolated testing database.');
}
$source = json_decode(file_get_contents(base_path('docs/product/review-camino-pasajero-source-2026-09-20.json')), true, 512, JSON_THROW_ON_ERROR);
$planPath = base_path('docs/product/REVISION-EDITORIAL-PREPARADA-2026-09-29.json');
$plan = json_decode(file_get_contents($planPath), true, 512, JSON_THROW_ON_ERROR);
$proposals = array_column($plan['lessons'], null, 'lesson_id');
$originals = array_column($source['lessons'], null, 'id');
$results = [];
$protectedTables = ['academic_enrollments', 'academic_enrollment_lesson_completions', 'academic_descubro_progress'];
$fingerprint = static function () use ($protectedTables): array {
    $result = [];
    foreach ($protectedTables as $table) {
        $rows = DB::table($table)->get()->map(static fn ($row): string => json_encode($row, JSON_THROW_ON_ERROR))->all();
        sort($rows);
        $result[$table] = hash('sha256', implode("\n", $rows));
    }

    return $result;
};
DB::beginTransaction();
try {
    $beforeProgress = $fingerprint();
    $courses = app(CourseRepository::class);
    $contents = app(UnitContentRepository::class);
    foreach (['EDU-EXP-001' => 27, 'EDU-EXP-003' => 12] as $code => $expectedCount) {
        $course = $courses->findByCode(CourseCode::fromString($code));
        if ($course === null || $course->status()->value !== 'published') {
            throw new RuntimeException('Expected published course '.$code);
        }
        DB::table('academic_courses')->where('id', $course->id()->value())->lockForUpdate()->first();
        $oldVersion = (int) DB::table('academic_course_versions')->where('course_id', $course->id()->value())->max('version_number');
        $units = [];
        $count = 0;
        foreach ($course->modules() as $module) {
            foreach ($module->units() as $unit) {
                $content = $contents->findForCourseUnit($course->id(), $unit->id());
                if ($content === null) {
                    throw new RuntimeException('Missing unit content.');
                }
                $lessons = [];
                foreach ($content->lessons() as $lesson) {
                    $count++;
                    $id = $lesson->id()->value();
                    $original = $originals[$id] ?? throw new RuntimeException('Lesson outside approved source.');
                    $oldBlocks = array_map(static fn ($block): array => ['type' => $block->type()->value, 'payload' => $block->payload()], $lesson->blocks());
                    $design = $lesson->learningDesign()?->toArray();
                    if ($original['course'] !== $code || $lesson->title() !== $original['title'] || $design != $original['design'] || $oldBlocks != $original['blocks']) {
                        throw new RuntimeException('Content drift: '.$id);
                    }
                    // Explicit audience mapping from the editorial review: child accompanied
                    // except PR04, whose protection-system selection belongs to the adult.
                    $design['stage'] = $id === 'a024c611-7f44-58fa-9101-0b0ac85824ea' ? 'teach' : 'discover';
                    $design['requires_guardian'] = true;
                    $design['version']++;
                    $blocks = [];
                    foreach ($lesson->blocks() as $index => $block) {
                        $payload = $block->payload();
                        if (isset($proposals[$id])) {
                            $change = $proposals[$id]['blocks'][$index];
                            if ($change['id'] !== $block->id()->value() || $change['before'] != $payload || $change['position'] !== $block->position()) {
                                throw new RuntimeException('Block identity drift.');
                            }
                            $payload = $change['after'];
                        }
                        $blocks[] = ContentBlockFactory::create($block->id(), $block->type(), $block->position(), $payload);
                    }
                    $lessons[] = Lesson::create($lesson->id(), $lesson->code(), $lesson->title(), $lesson->summary(), $lesson->durationMinutes(), $lesson->position(), $blocks, LessonLearningDesign::fromArray($design));
                }
                $units[] = UnitContent::create($unit->id(), $lessons);
            }
        }
        if ($count !== $expectedCount) {
            throw new RuntimeException('Unexpected course scope.');
        }
        app(ReopenCourseHandler::class)->handle(new ReopenCourseCommand($course->id()->value()));
        foreach ($units as $unit) {
            $contents->replaceAtomically($course->id(), $unit->unitId(), $unit);
        }
        app(SubmitCourseForReviewHandler::class)->handle(new SubmitCourseForReviewCommand($course->id()->value()));
        app(ApproveCourseHandler::class)->handle(new ApproveCourseCommand($course->id()->value()));
        app(PublishCourseHandler::class)->handle(new PublishCourseCommand($course->id()->value()));
        $version = (int) DB::table('academic_course_versions')->where('course_id', $course->id()->value())->max('version_number');
        if ($version !== $oldVersion + 1) {
            throw new RuntimeException('Missing new version.');
        }
        app(AuditLogger::class)->log(new AuditEntry(action: 'academic.editorial_revision.published', entity: 'course', entityId: $course->id()->value(), metadata: [
            'revision' => $plan['revision'], 'plan_sha256' => hash_file('sha256', $planPath),
            'approval_basis' => 'User confirmed approval of both complete courses in conversation on 2026-09-29; no external certificate inspected.',
            'previous_version' => $oldVersion, 'new_version' => $version, 'lesson_count' => $count,
            'audience' => '9–12 accompanied; PR04 adult responsibility (teach)',
        ]));
        $results[] = ['course' => $code, 'id' => $course->id()->value(), 'previous_version' => $oldVersion, 'new_version' => $version, 'lessons' => $count];
    }
    $adminId = DB::table('authorization_role_assignments')->where('role', 'super_admin')->value('user_id');
    Auth::guard('web')->setUser(UserModel::findOrFail($adminId));
    $kernel = app(Illuminate\Contracts\Http\Kernel::class);
    foreach ($results as $result) {
        $response = $kernel->handle(Request::create('https://app.edudrive.vr506.com/courses/'.$result['id'].'/preview', 'GET'));
        if ($response->getStatusCode() !== 200 || ! str_contains($response->getContent(), 'Pistas, explicación y conversación')) {
            throw new RuntimeException('Published preview failed: '.$result['course']);
        }
    }
    if ($beforeProgress !== $fingerprint()) {
        throw new RuntimeException('Progress changed; aborting.');
    }
    if ($apply) {
        DB::commit();
    } else {
        DB::rollBack();
    }
    echo json_encode(['applied' => $apply, 'progress_unchanged' => true, 'courses' => $results], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), "\n";
} catch (Throwable $error) {
    DB::rollBack();
    throw $error;
}
