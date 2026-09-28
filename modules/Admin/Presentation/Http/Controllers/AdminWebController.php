<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Academic\Domain\Enums\ContentBlockType;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Admin\Application\Commands\ExportAuditLogsCommand;
use Modules\Admin\Application\Commands\SetSystemSettingCommand;
use Modules\Admin\Application\Queries\GetAuditLogsQuery;
use Modules\Admin\Application\Queries\GetSystemHealthQuery;
use Modules\Admin\Application\Queries\GetSystemSummaryQuery;
use Modules\Admin\Application\Queries\ListSystemSettingsQuery;
use Modules\Admin\Application\Responses\AuditLogResponse;
use Modules\Admin\Application\Responses\SystemHealthResponse;
use Modules\Admin\Application\Responses\SystemSettingResponse;
use Modules\Admin\Application\Responses\SystemSummaryResponse;
use Modules\Admin\Presentation\Http\Requests\SetSystemSettingRequest;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

final class AdminWebController
{
    private const array SETTING_INFORMATION = [
        'maintenance_mode' => [
            'label' => 'Modo de mantenimiento',
            'description' => 'Permite restringir temporalmente el acceso mientras se realizan labores técnicas.',
            'type' => 'boolean',
        ],
        'signup_enabled' => [
            'label' => 'Registro público de usuarios',
            'description' => 'Define si una persona puede crear una cuenta sin intervención administrativa.',
            'type' => 'boolean',
        ],
    ];

    public function summary(QueryBus $queryBus, CourseRepository $courses, UnitContentRepository $unitContent): View
    {
        $summary = $queryBus->ask(new GetSystemSummaryQuery);
        assert($summary instanceof SystemSummaryResponse);

        $quality = ['total' => 0, 'ready' => 0, 'issues' => [], 'reason_counts' => []];
        foreach ($courses->all() as $course) {
            foreach ($course->modules() as $module) {
                foreach ($module->units() as $unit) {
                    $content = $unitContent->findForCourseUnit($course->id(), $unit->id());
                    foreach ($content?->lessons() ?? [] as $lesson) {
                        $quality['total']++;
                        $reasons = [];
                        $design = $lesson->learningDesign();
                        if ($design === null) {
                            $reasons[] = 'Sin diseño pedagógico trazable';
                        }
                        if ($design !== null && $design->normativeSources === []) {
                            $reasons[] = 'Sin fuente editorial registrada';
                        }
                        if ($design !== null && $design->normativeSources !== [] && ! $design->sourcesAreCurrent(new DateTimeImmutable('today'))) {
                            $reasons[] = 'Fuente pendiente de revisión anual';
                        }
                        if ($lesson->durationMinutes() === null) {
                            $reasons[] = 'Sin duración estimada';
                        }
                        if (! collect($lesson->blocks())->contains(fn ($block): bool => $block->type() === ContentBlockType::Scenario)) {
                            $reasons[] = 'Sin decisión práctica evaluable';
                        }
                        if (count($lesson->blocks()) < 2) {
                            $reasons[] = 'Experiencia demasiado breve';
                        }
                        if ($reasons === []) {
                            $quality['ready']++;
                        } else {
                            foreach ($reasons as $reason) {
                                $quality['reason_counts'][$reason] = ($quality['reason_counts'][$reason] ?? 0) + 1;
                            }
                            $quality['issues'][] = ['course_id' => $course->id()->value(), 'course_code' => $course->code()->value(), 'course' => $course->title()->value(), 'lesson' => $lesson->title(), 'reasons' => $reasons];
                        }
                    }
                }
            }
        }
        $quality['percentage'] = $quality['total'] === 0 ? 0 : (int) round(($quality['ready'] / $quality['total']) * 100);
        arsort($quality['reason_counts']);

        return view('admin.summary', ['summary' => $summary->toArray(), 'quality' => $quality]);
    }

    public function operations(QueryBus $queryBus): View
    {
        $health = $queryBus->ask(new GetSystemHealthQuery);
        $logs = $queryBus->ask(new GetAuditLogsQuery);
        assert($health instanceof SystemHealthResponse);
        assert(is_array($logs));
        $logData = array_map(static fn (AuditLogResponse $log): array => $log->toArray(), $logs);
        $users = UserModel::query()
            ->whereIn('id', collect($logData)->pluck('user_id')->filter()->unique())
            ->get(['id', 'name', 'email'])
            ->keyBy('id');
        $logData = array_map(static function (array $log) use ($users): array {
            $actor = $users->get($log['user_id']);
            $log['actor_name'] = $actor?->name ?? ($log['user_id'] ? 'Usuario no disponible' : 'Sistema EDUDRIVE');
            $log['actor_email'] = $actor?->email;

            return $log;
        }, $logData);

        return view('admin.operations', [
            'health' => $health->toArray(),
            'logs' => $logData,
        ]);
    }

    public function settings(QueryBus $queryBus): View
    {
        $settings = $queryBus->ask(new ListSystemSettingsQuery);
        assert(is_array($settings));

        return view('admin.settings', [
            'settings' => array_map(static fn (SystemSettingResponse $setting): array => $setting->toArray(), $settings),
            'settingInformation' => self::SETTING_INFORMATION,
        ]);
    }

    public function updateSetting(string $key, SetSystemSettingRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $data = $request->validated();
        $commandBus->dispatch(new SetSystemSettingCommand(
            key: $key,
            value: (string) $data['value'],
            actorId: (string) $request->user()?->getAuthIdentifier(),
        ));

        return back()->with('status', 'Configuración actualizada.');
    }

    public function exportAuditLogs(Request $request, CommandBus $commandBus): RedirectResponse
    {
        $result = $commandBus->dispatch(new ExportAuditLogsCommand(
            requestedByUserId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof AsyncJobResponse);

        return back()->with('status', 'La exportación de auditoría fue solicitada. Podrás descargarla cuando termine el procesamiento.');
    }
}
