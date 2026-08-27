<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Notification\Domain\Aggregates\CommunicationTemplate;
use Modules\Notification\Domain\Enums\CommunicationTemplateStatus;
use Modules\Notification\Domain\Repositories\CommunicationTemplateRepository;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateCode;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateId;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Models\CommunicationTemplateModel;

final readonly class EloquentCommunicationTemplateRepository implements CommunicationTemplateRepository
{
    public function save(CommunicationTemplate $template): void
    {
        CommunicationTemplateModel::query()->updateOrCreate(
            ['id' => $template->id()->value()],
            [
                'code' => $template->code()->value(),
                'locale' => $template->locale(),
                'subject_template' => $template->subjectTemplate(),
                'body_template' => $template->bodyTemplate(),
                'variables' => $template->variables(),
                'version' => $template->version(),
                'status' => $template->status()->value,
                'registered_at' => $template->registeredAt(),
                'retired_at' => $template->retiredAt(),
                'retired_reason' => $template->retiredReason(),
            ],
        );
    }

    public function findById(CommunicationTemplateId $id): ?CommunicationTemplate
    {
        $model = CommunicationTemplateModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByCodeAndLocale(CommunicationTemplateCode $code, string $locale): ?CommunicationTemplate
    {
        $model = CommunicationTemplateModel::query()
            ->where('code', $code->value())
            ->where('locale', $locale)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<CommunicationTemplate> */
    public function all(): array
    {
        return array_values(
            CommunicationTemplateModel::query()
                ->orderBy('registered_at')
                ->get()
                ->map(fn (CommunicationTemplateModel $model): CommunicationTemplate => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(CommunicationTemplateModel $model): CommunicationTemplate
    {
        $retiredAt = $model->getAttribute('retired_at');
        $retiredReason = $model->getAttribute('retired_reason');

        /** @var list<string> $variables */
        $variables = $model->getAttribute('variables');

        return CommunicationTemplate::restore(
            id: CommunicationTemplateId::fromString((string) $model->getAttribute('id')),
            code: CommunicationTemplateCode::fromString((string) $model->getAttribute('code')),
            locale: (string) $model->getAttribute('locale'),
            subjectTemplate: (string) $model->getAttribute('subject_template'),
            bodyTemplate: (string) $model->getAttribute('body_template'),
            variables: $variables,
            version: (int) $model->getAttribute('version'),
            status: CommunicationTemplateStatus::from((string) $model->getAttribute('status')),
            registeredAt: new DateTimeImmutable((string) $model->getAttribute('registered_at')),
            retiredAt: $retiredAt === null ? null : new DateTimeImmutable((string) $retiredAt),
            retiredReason: $retiredReason === null ? null : (string) $retiredReason,
        );
    }
}
