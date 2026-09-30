<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Session-only rehearsal of the learner view. No academic evidence is emitted. */
final class PilotJourneyController
{
    private const KEY = 'p912_journey';

    private function guard(): void
    {
        abort_unless(
            app()->environment(['local', 'testing']) || config('pilot.instruments_enabled', false),
            404,
        );
    }

    /** @return array<string, mixed> */
    private function source(string $name): array
    {
        $source = file_get_contents(resource_path('curriculum/p912/'.$name));
        if ($source === false) {
            throw new \RuntimeException('No se pudo leer el material P912.');
        }

        return json_decode($source, true, 512, JSON_THROW_ON_ERROR);
    }

    public function show(Request $request): View
    {
        $this->guard();
        $run = $request->session()->get(self::KEY);
        abort_if($run !== null && $run['owner'] !== (string) $request->user()?->getAuthIdentifier(), 404);
        $step = $run === null ? null : ($run['steps'][$run['position']] ?? null);
        $history = $run === null ? [] : ($run['answers'][$run['position']] ?? []);
        if ($step !== null && $history === []) {
            unset($step['explanation']);
        }
        if ($step !== null && ! ($run['hints'][$run['position']] ?? false)) {
            unset($step['hint']);
        }

        // Pass only the current task; future assessment tasks never enter the HTML.
        return view('courses.pilot-journey', [
            'started' => $run !== null, 'finished' => $run !== null && $step === null,
            'step' => $step, 'history' => $history, 'position' => $run['position'] ?? 0,
            'revision' => $run['revision'] ?? 0, 'token' => $run['token'] ?? '',
            'summary' => $run !== null && $step === null ? $run['answers'] : [],
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $this->guard();
        $request->validate(['synthetic_only' => ['accepted']]);
        // Do not silently erase an existing rehearsal.
        abort_if($request->session()->has(self::KEY), 409, 'Ya existe un recorrido en esta sesión.');
        $bank = $this->source('instruments.v0.1.json');
        $unit = $this->source('crossing-unit.v0.1.json');
        $steps = [];
        foreach (['D01', 'D02', 'D04'] as $id) {
            foreach ($bank['items'] as $item) {
                if ($item['id'] === $id) {
                    $steps[] = array_intersect_key($item, array_flip(['id', 'title', 'scene', 'task'])) + ['phase' => 'Observo y respondo'];
                }
            }
        }
        foreach ($unit['activities'] as $activity) {
            $scene = match ($activity['id']) {
                'U01-A' => 'Luna está con una persona adulta en la acera. Un puesto cerrado tapa parte de la calle. Hay otro espacio libre dentro de la acera. Imaginá la escena o representala con objetos sobre una mesa.',
                'U01-B' => 'Luna sigue en la acera, acompañada. La señal peatonal permite avanzar, pero un vehículo empieza a girar y su recorrido pasa por el cruce. Todavía no entraron en la calle.',
                'U01-C' => 'Luna y una persona adulta encuentran una barrera que interrumpe la acera. No ven una alternativa protegida para continuar. Por ahora permanecen en la acera.',
                default => throw new \RuntimeException('Actividad sin escena para el participante.'),
            };
            $steps[] = ['id' => $activity['id'], 'title' => $activity['title'],
                'scene' => $scene, 'task' => $activity['prompt'],
                'hint' => $activity['hint'], 'explanation' => $activity['explanation'], 'phase' => 'Aprendo y practico'];
        }
        foreach (['C01', 'C02', 'C04'] as $id) {
            foreach ($bank['items'] as $item) {
                if ($item['id'] === $id) {
                    $steps[] = array_intersect_key($item, array_flip(['id', 'title', 'scene', 'task'])) + ['phase' => 'Resuelvo otra situación'];
                }
            }
        }
        $request->session()->put(self::KEY, ['owner' => (string) $request->user()?->getAuthIdentifier(),
            'token' => (string) Str::uuid(), 'version' => $unit['version'], 'bank_version' => $bank['version'],
            'steps' => $steps, 'position' => 0, 'revision' => 0, 'answers' => [], 'hints' => []]);

        return redirect()->route('pilot-instruments.journey');
    }

    public function update(Request $request): RedirectResponse
    {
        $this->guard();
        $data = $request->validate(['revision' => ['required', 'integer'], 'token' => ['required', 'uuid'],
            'action' => ['required', 'in:answer,hint,next'], 'response' => ['nullable', 'string', 'max:2000'],
            'support' => ['nullable', 'string', 'max:500']]);
        $run = $request->session()->get(self::KEY);
        abort_if($run === null || $run['owner'] !== (string) $request->user()?->getAuthIdentifier(), 404);
        abort_unless($run['token'] === $data['token'] && $run['revision'] === (int) $data['revision'], 409, 'El recorrido cambió. Recargá antes de continuar.');
        $position = $run['position'];
        $step = $run['steps'][$position] ?? null;
        abort_if($step === null, 409, 'Este recorrido ya terminó.');
        if ($data['action'] === 'answer') {
            $attempts = $run['answers'][$position] ?? [];
            abort_if(count($attempts) >= 10, 422, 'Podés continuar a la siguiente situación.');
            abort_if($attempts !== [] && ! isset($step['hint']), 422);
            $run['answers'][$position][] = ['item' => $step['id'], 'response' => $data['response'] ?? '',
                'support' => $data['support'] ?? '', 'hint_used' => $run['hints'][$position] ?? false,
                'formative' => isset($step['hint']) || trim($data['support'] ?? '') !== '', 'at' => now()->toIso8601String()];
        } elseif ($data['action'] === 'hint') {
            abort_unless(isset($step['hint']), 422);
            $run['hints'][$position] = true;
        } else {
            abort_unless(isset($run['answers'][$position]), 422, 'Guardá tu respuesta, aunque esté en blanco, antes de continuar.');
            $run['position']++;
        }
        $run['revision']++;
        $request->session()->put(self::KEY, $run);

        return redirect()->route('pilot-instruments.journey');
    }
}
