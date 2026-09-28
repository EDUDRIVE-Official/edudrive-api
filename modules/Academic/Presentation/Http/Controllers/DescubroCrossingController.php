<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Academic\Infrastructure\Services\DescubroPracticeProgress;
use Modules\Academic\Presentation\ViewModels\DescubroPracticeSummary;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;

/** Internal learner experience. Account progress never emits academic evidence. */
final class DescubroCrossingController
{
    public static function enabled(): bool
    {
        if (app()->environment(['local', 'testing'])
            || (app()->environment('staging') && config('descubro.staging_enabled') === true)) {
            return true;
        }
        if (! app()->environment('production') || config('descubro.production_review_enabled') !== true) {
            return false;
        }
        $user = auth('web')->user();
        if ($user === null || $user->status !== 'active') {
            return false;
        }
        foreach (app(RoleAssignmentRepository::class)->findByUserId((string) $user->getAuthIdentifier()) as $assignment) {
            if ($assignment->role() === Role::SuperAdmin && $assignment->organizationId() === null) {
                return true;
            }
        }

        return false;
    }

    public static function key(string $userId): string
    {
        return 'descubro_crossing_v1.'.hash('sha256', $userId);
    }

    /** @return array<string, mixed> */
    public static function initial(): array
    {
        return ['phase' => 'home', 'lesson' => 0, 'question' => 0, 'feedback' => null,
            'choice' => null, 'attempts' => [], 'completed_once' => false, 'revision' => 0];
    }

    /** @return array<string, mixed> */
    public static function progress(Request $request): array
    {
        $user = $request->user();
        abort_unless($user instanceof Authenticatable, 401);
        $userId = (string) $user->getAuthIdentifier();
        $key = self::key($userId);
        $state = app(DescubroPracticeProgress::class)->resume($userId, $request->session()->get($key));
        // Forget the legacy copy only after a durable read/import succeeds.
        if ($state !== null) {
            $request->session()->forget($key);
        }

        return $state ?? self::initial();
    }

    /** @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public static function summary(array $run): array
    {
        return DescubroPracticeSummary::fromRun($run, self::questions());
    }

    /** @return list<array<string, mixed>> */
    public static function questions(): array
    {
        return [
            ['title' => '¿Por dónde caminamos?', 'prompt' => 'Tito va al parque con su acompañante. Elegí un lugar para caminar.', 'scene' => 'bus', 'skill' => 'S1',
                'choices' => ['Por la calzada', 'Por la acera'], 'correct' => 1,
                'success' => 'La acera es el espacio para caminar. Nos quedamos juntos.',
                'retry' => 'Por la calzada pasan vehículos. Practiquemos buscar la acera con la persona adulta.'],
            ['title' => 'Viene un bus. ¿Qué hacemos?', 'prompt' => 'Estamos en la acera, antes del cruce.', 'scene' => 'bus', 'skill' => 'S2',
                'choices' => ['Entrar antes de que llegue', 'Esperar en la acera con la persona adulta', 'Ir por la pelota'], 'correct' => 1,
                'success' => 'Nos detenemos antes de la calzada y esperamos con la persona adulta. La pelota puede esperar.',
                'retry' => 'Hacemos una pausa. Entrar delante del bus o seguir la pelota nos expone al tránsito. Nos quedamos en la acera.'],
            ['title' => 'El bus pasó. ¿Ya cruzamos?', 'prompt' => 'La persona adulta todavía no ha indicado que pueden cruzar.', 'scene' => 'clear', 'skill' => 'S2',
                'choices' => ['Correr porque ya no está el bus', 'Comprobar otra vez con mi acompañante'], 'correct' => 1,
                'success' => 'Que pase el bus no significa que el cruce esté libre. Comprobamos todas las aproximaciones con la persona adulta.',
                'retry' => 'Todavía falta comprobar: puede venir otro vehículo. Nos quedamos en la acera y comprobamos con el acompañante.'],
            ['title' => 'Ahora podemos cruzar juntos.', 'prompt' => 'En esta escena, el acompañante comprobó el entorno y dio la indicación.', 'scene' => 'clear', 'skill' => 'S3',
                'choices' => ['Cruzar junto a mi acompañante', 'Salir primero y esperar al otro lado'], 'correct' => 0,
                'success' => 'Llegamos juntos. Conservamos la compañía hasta la otra acera.',
                'retry' => 'No salimos primero. Después de comprobar y recibir la indicación, cruzamos junto a la persona adulta.'],
        ];
    }

    /** @return list<array{title: string, text: string, scene: string}> */
    private function lessons(): array
    {
        return [
            ['title' => 'Camino por la acera', 'text' => 'Voy con una persona adulta. Reconozco la acera y la calzada.', 'scene' => 'bus'],
            ['title' => 'Me detengo y comprobamos', 'text' => 'Antes de entrar en la calzada hacemos una pausa y comprobamos todas las aproximaciones.', 'scene' => 'bus'],
            ['title' => 'Cruzamos juntos', 'text' => 'Cuando mi acompañante indica que podemos cruzar, voy a su lado hasta la otra acera.', 'scene' => 'arrived'],
        ];
    }

    public function show(Request $request): View
    {
        abort_unless(self::enabled(), 404);
        $run = self::progress($request);
        $page = $request->query('page', $run['phase']);
        abort_unless(is_string($page) && in_array($page, ['home', 'learn', 'practice', 'result', 'adult', 'circuit', 'review'], true), 404);
        if ($page === 'review') {
            $reviewIndex = $request->query('lesson', '0');
            abort_unless(is_string($reviewIndex) && in_array($reviewIndex, ['0', '1', '2'], true), 404);

            return view('descubro.crossing', $this->viewData($run, $page) + [
                'reviewIndex' => (int) $reviewIndex,
                'reviewLesson' => $this->lessons()[(int) $reviewIndex],
            ]);
        }
        if (in_array($page, ['learn', 'practice', 'result'], true) && $page !== $run['phase']) {
            return view('descubro.crossing', $this->viewData($run, $run['phase']));
        }

        return view('descubro.crossing', $this->viewData($run, $page));
    }

    /** @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function viewData(array $run, string $page): array
    {
        $question = self::questions()[$run['question']];
        $feedback = $run['feedback'] === null ? null : $question[$run['feedback'] === 'success' ? 'success' : 'retry'];
        unset($question['correct'], $question['success'], $question['retry']);

        return ['practiceSummary' => self::summary($run), 'run' => $run, 'page' => $page, 'question' => $question, 'feedback' => $feedback,
            'lesson' => $this->lessons()[$run['lesson']], 'lessons' => $this->lessons()];
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(self::enabled(), 404);
        $data = $request->validate([
            'action' => ['required', 'in:start,lesson-next,practice-start,answer,retry,next'],
            'revision' => ['required', 'integer', 'min:0'],
            'choice' => ['required_if:action,answer', 'nullable', 'integer', 'min:0', 'max:2'],
        ]);
        $user = $request->user();
        abort_unless($user instanceof Authenticatable, 401);
        $userId = (string) $user->getAuthIdentifier();
        $run = self::progress($request);
        abort_unless((int) $data['revision'] === $run['revision'], 409, 'El recorrido cambió. Recargá antes de continuar.');

        switch ($data['action']) {
            case 'start':
                abort_unless($run['phase'] === 'home', 422);
                $run['phase'] = 'learn';
                break;
            case 'lesson-next':
                abort_unless($run['phase'] === 'learn' && $run['lesson'] < 2, 422);
                $run['lesson']++;
                break;
            case 'practice-start':
                abort_unless(($run['phase'] === 'learn' && $run['lesson'] === 2) || $run['phase'] === 'result', 422);
                $run['phase'] = 'practice';
                $run['question'] = 0;
                $run['feedback'] = null;
                $run['choice'] = null;
                break;
            case 'answer':
                abort_unless($run['phase'] === 'practice' && $run['feedback'] === null, 422);
                $question = self::questions()[$run['question']];
                $choice = (int) $data['choice'];
                abort_unless(array_key_exists($choice, $question['choices']), 422);
                $correct = $choice === $question['correct'];
                $run['choice'] = $choice;
                $run['feedback'] = $correct ? 'success' : 'retry';
                $run['attempts'][] = ['question' => $run['question'], 'skill' => $question['skill'], 'choice' => $choice, 'correct' => $correct];
                $run['attempts'] = array_slice($run['attempts'], -80);
                break;
            case 'retry':
                abort_unless($run['phase'] === 'practice' && $run['feedback'] === 'retry', 422);
                $run['feedback'] = null;
                $run['choice'] = null;
                break;
            case 'next':
                abort_unless($run['phase'] === 'practice' && $run['feedback'] === 'success', 422);
                if ($run['question'] === 3) {
                    $run['phase'] = 'result';
                    $run['completed_once'] = true;
                } else {
                    $run['question']++;
                    $run['feedback'] = null;
                    $run['choice'] = null;
                }
                break;
        }

        app(DescubroPracticeProgress::class)->save($userId, $run, (int) $data['revision']);

        // Keep post/redirect/get on this origin, including embedded local previews.
        return new RedirectResponse(route('descubro.crossing.show', [], false));
    }
}
