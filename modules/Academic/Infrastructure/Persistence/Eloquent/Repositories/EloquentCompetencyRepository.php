<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Competency;
use Modules\Academic\Domain\Entities\CompetencyIndicator;
use Modules\Academic\Domain\Entities\Subcompetency;
use Modules\Academic\Domain\Enums\CompetencyCategory;
use Modules\Academic\Domain\Enums\MasteryLevel;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyCode;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CompetencyIndicatorModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CompetencyModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\SubcompetencyModel;

final class EloquentCompetencyRepository implements CompetencyRepository
{
    public function save(Competency $competency): void
    {
        DB::transaction(function () use ($competency): void {
            $model = CompetencyModel::query()->updateOrCreate(
                ['id' => $competency->id()->value()],
                [
                    'code' => $competency->code()->value(),
                    'title' => $competency->title(),
                    'description' => $competency->description(),
                    'category' => $competency->category()->value,
                    'mastery_level' => $competency->masteryLevel()->value,
                    'status' => $competency->isActive() ? 'active' : 'inactive',
                ],
            );

            $model->subcompetencies()->delete();
            foreach ($competency->subcompetencies() as $subcompetency) {
                $subcompetencyModel = SubcompetencyModel::query()->create([
                    'id' => (string) Str::uuid(),
                    'competency_id' => $model->getKey(),
                    'code' => $subcompetency->code(),
                    'title' => $subcompetency->title(),
                    'position' => $subcompetency->position(),
                ]);
                foreach ($subcompetency->indicators() as $indicator) {
                    CompetencyIndicatorModel::query()->create([
                        'id' => (string) Str::uuid(),
                        'subcompetency_id' => $subcompetencyModel->getKey(),
                        'code' => $indicator->code(),
                        'description' => $indicator->description(),
                        'position' => $indicator->position(),
                    ]);
                }
            }
        });
    }

    public function findById(CompetencyId $id): ?Competency
    {
        $model = CompetencyModel::query()->with('subcompetencies.indicators')->find($id->value());

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByCode(CompetencyCode $code): ?Competency
    {
        $model = CompetencyModel::query()->with('subcompetencies.indicators')->where('code', $code->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function existsByCode(CompetencyCode $code): bool
    {
        return CompetencyModel::query()->where('code', $code->value())->exists();
    }

    public function all(): array
    {
        $competencies = CompetencyModel::query()->with('subcompetencies.indicators')->orderBy('code')->get()
            ->map(fn (CompetencyModel $model): Competency => $this->toDomain($model))->all();

        return array_values($competencies);
    }

    private function toDomain(CompetencyModel $model): Competency
    {
        $subcompetencies = $model->subcompetencies->map(function (SubcompetencyModel $subcompetency): Subcompetency {
            $indicators = $subcompetency->indicators->map(fn (CompetencyIndicatorModel $indicator): CompetencyIndicator => CompetencyIndicator::restore(
                (string) $indicator->getAttribute('code'),
                (string) $indicator->getAttribute('description'),
                (int) $indicator->getAttribute('position'),
            ))->all();

            return Subcompetency::restore(
                (string) $subcompetency->getAttribute('code'),
                (string) $subcompetency->getAttribute('title'),
                (int) $subcompetency->getAttribute('position'),
                array_values($indicators),
            );
        })->all();

        return Competency::restore(
            CompetencyId::fromString((string) $model->getAttribute('id')),
            CompetencyCode::fromString((string) $model->getAttribute('code')),
            (string) $model->getAttribute('title'),
            (string) $model->getAttribute('description'),
            CompetencyCategory::from((string) $model->getAttribute('category')),
            MasteryLevel::from((string) $model->getAttribute('mastery_level')),
            $model->getAttribute('status') === 'active',
            array_values($subcompetencies),
        );
    }
}
