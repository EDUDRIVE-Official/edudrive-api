<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseModel;

uses(RefreshDatabase::class);

it('lists academic courses', function (): void {
    CourseModel::query()->create([
        'id' => '3fdab59d-7431-440f-bdab-f55798e99a79',
        'code' => 'SV-001',
        'title' => 'Seguridad Vial',
        'description' => 'Curso introductorio de seguridad vial.',
        'status' => 'draft',
        'published_at' => null,
        'archived_at' => null,
    ]);

    CourseModel::query()->create([
        'id' => '0844b7fa-5d71-41c6-a59d-864cf7927cc3',
        'code' => 'MOT-001',
        'title' => 'Conducción de Motocicletas',
        'description' => null,
        'status' => 'draft',
        'published_at' => null,
        'archived_at' => null,
    ]);

    $response = $this->getJson('/api/v1/academic/courses');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.code', 'SV-001')
        ->assertJsonPath('data.0.title', 'Seguridad Vial')
        ->assertJsonPath('data.0.status', 'draft')
        ->assertJsonPath('data.1.code', 'MOT-001')
        ->assertJsonPath('data.1.title', 'Conducción de Motocicletas')
        ->assertJsonPath('data.1.status', 'draft');
});
