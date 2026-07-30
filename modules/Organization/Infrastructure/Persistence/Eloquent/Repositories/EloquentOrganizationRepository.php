<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Entities\Campus;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Models\CampusModel;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Models\OrganizationModel;

final class EloquentOrganizationRepository implements OrganizationRepository
{
    public function save(Organization $organization): void
    {
        DB::transaction(function () use ($organization): void {
            OrganizationModel::query()->updateOrCreate(
                ['id' => $organization->id()->value()],
                [
                    'name' => $organization->name()->value(),
                    'type' => $organization->type()->value,
                ],
            );

            CampusModel::query()
                ->where('organization_id', $organization->id()->value())
                ->delete();

            foreach ($organization->campuses() as $campus) {
                CampusModel::query()->create([
                    'id' => $campus->id(),
                    'organization_id' => $organization->id()->value(),
                    'name' => $campus->name(),
                ]);
            }
        });
    }

    public function findById(OrganizationId $id): ?Organization
    {
        $model = OrganizationModel::query()
            ->with('campuses')
            ->find($id->value());

        return $model === null
            ? null
            : $this->toDomain($model);
    }

    /**
     * @return list<Organization>
     */
    public function all(): array
    {
        $organizations = OrganizationModel::query()
            ->orderBy('created_at')
            ->with('campuses')
            ->get()
            ->map(
                fn (OrganizationModel $model): Organization => $this->toDomain($model),
            )
            ->all();

        return array_values($organizations);
    }

    private function toDomain(OrganizationModel $model): Organization
    {
        $campuses = $model->campuses
            ->sortBy('created_at')
            ->map(
                static fn (CampusModel $campusModel): Campus => Campus::create(
                    id: (string) $campusModel->getAttribute('id'),
                    name: (string) $campusModel->getAttribute('name'),
                ),
            )
            ->all();

        return Organization::restore(
            id: OrganizationId::fromString((string) $model->getAttribute('id')),
            name: OrganizationName::fromString((string) $model->getAttribute('name')),
            type: OrganizationType::from((string) $model->getAttribute('type')),
            campuses: array_values($campuses),
        );
    }
}
