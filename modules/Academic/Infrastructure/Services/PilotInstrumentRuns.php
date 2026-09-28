<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Isolated internal rehearsal: no enrollment, passport, grading or certification writes. */
final class PilotInstrumentRuns
{
    public const FORMS = ['diagnostic', 'practice', 'independent', 'retention'];

    public function start(string $owner, string $form): string
    {
        abort_unless(in_array($form, self::FORMS, true), 422);
        $source = file_get_contents(resource_path('curriculum/p912/instruments.v0.1.json'));
        if ($source === false) {
            throw new \RuntimeException('No se pudo leer el banco P912.');
        }
        $bank = json_decode($source, true, 512, JSON_THROW_ON_ERROR);
        // Snapshot only content needed by the runner, never evaluator criteria.
        $items = [];
        foreach ($bank['items'] as $item) {
            if ($item['form'] !== $form) {
                continue;
            }
            $items[] = array_intersect_key($item, array_flip(['id', 'title', 'scene', 'task', 'hints', 'feedback']));
        }
        $id = (string) Str::uuid();
        DB::table('academic_pilot_runs')->insert([
            'id' => $id, 'owner_user_id' => $owner, 'form' => $form,
            'instrument_version' => $bank['version'], 'status' => 'open', 'revision' => 0,
            'items' => json_encode($items, JSON_THROW_ON_ERROR), 'events' => '[]',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    /** @return array<string, mixed> */
    public function read(string $id, string $owner): array
    {
        $row = DB::table('academic_pilot_runs')->where('id', $id)->where('owner_user_id', $owner)->first();
        abort_if($row === null, 404);
        $items = json_decode($row->items, true, 512, JSON_THROW_ON_ERROR);
        $events = json_decode($row->events, true, 512, JSON_THROW_ON_ERROR);
        foreach ($items as &$item) {
            $usedHints = count(array_filter($events, fn ($event) => $event['type'] === 'hint' && ($event['item_id'] ?? null) === $item['id']));
            $item['can_hint'] = $row->status === 'open' && $row->form === 'practice' && $usedHints < count($item['hints'] ?? []);
            $answered = false;
            foreach ($events as $event) {
                if (($event['item_id'] ?? null) === $item['id'] && $event['type'] === 'response') {
                    $answered = true;
                }
            }
            // Feedback appears only after a response in practice. Independent forms have none.
            if (! $answered || $row->form !== 'practice') {
                unset($item['feedback']);
            }
            unset($item['hints']);
        }
        unset($item);

        return ['id' => $row->id, 'form' => $row->form, 'version' => $row->instrument_version,
            'status' => $row->status, 'revision' => $row->revision, 'items' => $items, 'events' => $events];
    }

    /** @param array<string, mixed> $input */
    public function append(string $id, string $owner, int $revision, string $action, array $input): void
    {
        DB::transaction(function () use ($id, $owner, $revision, $action, $input): void {
            $row = DB::table('academic_pilot_runs')->where('id', $id)->where('owner_user_id', $owner)->lockForUpdate()->first();
            abort_if($row === null, 404);
            abort_unless($row->status === 'open' && (int) $row->revision === $revision, 409, 'La sesión cambió o ya se cerró. Recargá antes de continuar.');
            $items = json_decode($row->items, true, 512, JSON_THROW_ON_ERROR);
            $events = json_decode($row->events, true, 512, JSON_THROW_ON_ERROR);
            abort_if(count($events) >= 200 && $action !== 'finish', 422, 'Límite del ensayo alcanzado; podés cerrarlo.');
            $item = null;
            foreach ($items as $candidate) {
                if ($candidate['id'] === ($input['item_id'] ?? '')) {
                    $item = $candidate;
                    break;
                }
            }
            $event = ['type' => $action, 'recorded_at' => now()->toIso8601String()];
            if ($action === 'response') {
                abort_if($item === null, 422);
                $hasContentHelp = trim($input['content_help'] ?? '') !== '';
                foreach ($events as $prior) {
                    if (($prior['item_id'] ?? null) === $item['id'] && ($prior['type'] === 'hint' || trim($prior['content_help'] ?? '') !== '')) {
                        $hasContentHelp = true;
                    }
                }
                $event += ['item_id' => $item['id'], 'response' => $input['response'] ?? '',
                    'access_support' => $input['access_support'] ?? '', 'content_help' => $input['content_help'] ?? '',
                    'help_source' => 'facilitator_reported',
                    'response_context' => $row->form === 'practice' || $hasContentHelp ? 'formative' : 'without_content_help_reported',
                    'feedback_available_after_response' => $row->form === 'practice'];
            } elseif ($action === 'hint') {
                abort_unless($row->form === 'practice' && $item !== null, 422);
                $used = count(array_filter($events, fn ($e) => $e['type'] === 'hint' && $e['item_id'] === $item['id']));
                abort_unless(isset($item['hints'][$used]), 422, 'No quedan pistas.');
                $event += ['item_id' => $item['id'], 'text' => $item['hints'][$used]];
            } elseif ($action === 'finish') {
                $answered = array_column(array_filter($events, fn ($e) => $e['type'] === 'response'), 'item_id');
                $event += ['unanswered_items' => array_values(array_diff(array_column($items, 'id'), $answered)),
                    'evidence_scope' => 'internal_rehearsal', 'demonstrates_mastery' => false];
            } else {
                abort(422);
            }
            $events[] = $event;
            $changed = DB::table('academic_pilot_runs')->where('id', $id)->where('revision', $revision)->update([
                'events' => json_encode($events, JSON_THROW_ON_ERROR), 'revision' => $revision + 1,
                'status' => $action === 'finish' ? 'closed' : 'open', 'updated_at' => now(),
            ]);
            abort_unless($changed === 1, 409);
        });
    }
}
