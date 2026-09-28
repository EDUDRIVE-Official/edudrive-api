<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class EditorialPreviewController
{
    public function __invoke(Request $request): View
    {
        abort_unless(app()->environment(['local', 'testing']) || config('pilot.instruments_enabled', false), 404);
        $data = $request->validate([
            'lesson' => ['sometimes', 'integer', 'between:1,5'],
            'page' => ['sometimes', 'integer', 'between:1,5'],
        ]);
        $json = file_get_contents(resource_path('curriculum/editorial/camino-pasajero-v1.json'));
        abort_if($json === false, 500, 'No se pudo leer el material de revisión.');
        $draft = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $number = (int) ($data['lesson'] ?? 1);
        $supports = require resource_path('curriculum/editorial/page-support.php');

        return view('courses.editorial-preview', [
            'lessons' => $draft['lessons'], 'lesson' => $draft['lessons'][$number - 1], 'number' => $number,
            'page' => (int) ($data['page'] ?? 1),
            'support' => $supports[$number],
        ]);
    }
}
