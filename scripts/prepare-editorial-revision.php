<?php

declare(strict_types=1);

// Read-only by default. The optional rehearsal rolls back and is restricted to
// a specifically named isolated testing database. Never publishes a course.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\LessonModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

$rehearse = in_array('--rehearse', $argv, true);
if ($rehearse && (! app()->environment('testing') || DB::connection()->getDatabaseName() !== 'edudrive_editorial_rehearsal')) {
    throw new RuntimeException('Rehearsal requires testing and edudrive_editorial_rehearsal.');
}
$source = json_decode(file_get_contents(base_path('docs/product/review-camino-pasajero-source-2026-09-20.json')), true, 512, JSON_THROW_ON_ERROR);
$draft = json_decode(file_get_contents(resource_path('curriculum/editorial/camino-pasajero-v1.json')), true, 512, JSON_THROW_ON_ERROR);
$supports = require resource_path('curriculum/editorial/page-support.php');
$list = static fn (array $items): string => implode("\n", array_map(static fn (string $item): string => '- '.$item, $items));
$plan = ['revision' => 'camino-pasajero-20260929', 'status' => 'pending_review', 'production_changes' => false, 'lessons' => []];
DB::beginTransaction();
try {
    if (! $rehearse && DB::connection()->getDriverName() === 'pgsql') {
        DB::statement('SET TRANSACTION READ ONLY');
    }
    if (count($draft['lessons']) !== 5) {
        throw new RuntimeException('Expected exactly five lessons.');
    }
    foreach ($draft['lessons'] as $number => $proposal) {
        $live = LessonModel::with('blocks')->findOrFail($proposal['lesson_id']);
        $matches = array_values(array_filter($source['lessons'], static fn (array $row): bool => $row['id'] === $proposal['lesson_id']));
        if (count($matches) !== 1) {
            throw new RuntimeException('Missing or ambiguous source lesson.');
        }
        $original = $matches[0];
        $current = $live->blocks->map(static fn ($block): array => ['type' => $block->type, 'payload' => $block->payload])->all();
        if ($live->title !== $original['title'] || $live->learning_design != $original['design'] || $current != $original['blocks'] || count($current) !== 4) {
            throw new RuntimeException('Source changed; reconcile before updating '.$live->id);
        }
        $support = $supports[$number + 1];
        $supplements = [
            '## Tu misión'."\n\n".$support['mission']."\n\n".$proposal['instruction']."\n\n## Dos pistas para observar\n\n".
                implode("\n\n", array_map(static fn (array $clue): string => '### '.$clue[0]."\n\n".$clue[1], $support['clues']))."\n\n## Quiero saber más\n\n".$support['more'],
            "## Contalo con tus palabras\n\n".$support['reflection'][0]."\n\nPodés señalar, describir o usar tarjetas; no hace falta salir a una calle real.",
            "## Contalo con tus palabras\n\n".$support['reflection'][1]."\n\nPodés señalar, describir o usar tarjetas; no hace falta salir a una calle real.",
            "## Tres cosas para mostrar\n\n".$list($support['activity'])."\n\n## Lo que te llevás\n\n".$support['takeaway']."\n\n".$list($support['remember']).
                "\n\n## Una situación nueva para conversar\n\n".$proposal['transfer']['case']."\n\n".$proposal['transfer']['prompt'].
                "\n\nImaginen o dibujen la situación dentro del aula o en casa; no es una práctica en la calle.\n\n## Guía del acompañante\n\n".$proposal['adult'].
                "\n\n### Qué observar en la explicación\n\n".$list($proposal['transfer']['observe']),
        ];
        $revision = ['lesson_id' => $live->id, 'title' => $live->title, 'course_code' => $proposal['course_code'], 'design_unchanged' => $live->learning_design, 'blocks' => []];
        foreach ($proposal['blocks'] as $index => $block) {
            $persisted = $live->blocks[$index];
            if ($block['type'] !== $persisted->type) {
                throw new RuntimeException('Block type changed.');
            }
            if ($block['type'] === 'scenario') {
                $oldChoices = array_column($persisted->payload['choices'], 'correct', 'id');
                $newChoices = array_column($block['payload']['choices'], 'correct', 'id');
                if ($oldChoices != $newChoices || $block['payload']['title'] !== $persisted->payload['title']) {
                    throw new RuntimeException('Scenario identity or correct answer changed.');
                }
            }
            $payload = $block['payload'] + ['supplement' => $supplements[$index]];
            $validated = ContentBlockFactory::create(ContentBlockId::fromString($persisted->id), $block['type'], $persisted->position, $payload)->payload();
            $revision['blocks'][] = ['id' => $persisted->id, 'type' => $persisted->type, 'position' => $persisted->position, 'before' => $persisted->payload, 'after' => $validated];
        }
        $plan['lessons'][] = $revision;
    }
    // Build the complete plan before any rehearsal write; preserve every ID.
    if ($rehearse) {
        foreach ($plan['lessons'] as $lesson) {
            foreach ($lesson['blocks'] as $block) {
                DB::table('academic_lesson_blocks')->where('id', $block['id'])->update(['payload' => json_encode($block['after'], JSON_THROW_ON_ERROR)]);
            }
        }
        $adminId = DB::table('authorization_role_assignments')->where('role', 'super_admin')->value('user_id');
        Auth::guard('web')->setUser(UserModel::findOrFail($adminId));
        $kernel = app(Illuminate\Contracts\Http\Kernel::class);
        foreach (['EDU-EXP-001', 'EDU-EXP-003'] as $code) {
            $id = DB::table('academic_courses')->where('code', $code)->value('id');
            $response = $kernel->handle(Request::create('https://app.edudrive.vr506.com/courses/'.$id.'/preview', 'GET'));
            if ($response->getStatusCode() !== 200 || ! str_contains($response->getContent(), 'Pistas, explicación y conversación')) {
                throw new RuntimeException('Integrated course preview failed: '.$code);
            }
        }
        $plan['rehearsal'] = 'Both integrated course previews returned 200; transaction rolled back.';
    }
} finally {
    DB::rollBack();
}
echo json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
