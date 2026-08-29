<?php

declare(strict_types=1);

namespace Modules\Certification\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Enums\CertificateStatus;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateHistoryEntry;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;
use Modules\Certification\Infrastructure\Persistence\Eloquent\Models\CertificateHistoryEntryModel;
use Modules\Certification\Infrastructure\Persistence\Eloquent\Models\CertificateModel;

final readonly class EloquentCertificateRepository implements CertificateRepository
{
    public function save(Certificate $certificate): void
    {
        DB::transaction(function () use ($certificate): void {
            $model = CertificateModel::query()->updateOrCreate(
                ['id' => $certificate->id()->value()],
                [
                    'user_id' => $certificate->userId(),
                    'course_id' => $certificate->courseId(),
                    'validation_code' => $certificate->validationCode()->value(),
                    'status' => $certificate->status()->value,
                    'issued_at' => $certificate->issuedAt(),
                    'expires_at' => $certificate->expiresAt(),
                ],
            );

            $model->historyEntries()->delete();

            foreach ($certificate->history() as $entry) {
                CertificateHistoryEntryModel::query()->create([
                    'id' => (string) Str::uuid(),
                    'certificate_id' => $model->id,
                    'from_status' => $entry->fromStatus->value,
                    'to_status' => $entry->toStatus->value,
                    'reason' => $entry->reason,
                    'occurred_at' => $entry->occurredAt,
                ]);
            }
        });
    }

    public function findById(CertificateId $id): ?Certificate
    {
        $model = CertificateModel::query()->with('historyEntries')->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByUserAndCourse(string $userId, string $courseId): ?Certificate
    {
        $model = CertificateModel::query()->with('historyEntries')
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByValidationCode(ValidationCode $validationCode): ?Certificate
    {
        $model = CertificateModel::query()->with('historyEntries')
            ->where('validation_code', $validationCode->value())
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<Certificate> */
    public function allForUser(string $userId): array
    {
        return array_values(
            CertificateModel::query()->with('historyEntries')
                ->where('user_id', $userId)
                ->orderBy('issued_at')
                ->get()
                ->map(fn (CertificateModel $model): Certificate => $this->toDomain($model))
                ->all(),
        );
    }

    /** @return list<Certificate> */
    public function all(): array
    {
        return array_values(
            CertificateModel::query()->with('historyEntries')
                ->orderBy('issued_at')
                ->get()
                ->map(fn (CertificateModel $model): Certificate => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(CertificateModel $model): Certificate
    {
        /** @var list<CertificateHistoryEntryModel> $historyModels */
        $historyModels = array_values($model->historyEntries->all());

        $expiresAt = $model->getAttribute('expires_at');
        $userId = $model->getAttribute('user_id');

        return Certificate::restore(
            id: CertificateId::fromString((string) $model->getAttribute('id')),
            userId: $userId === null ? null : (string) $userId,
            courseId: (string) $model->getAttribute('course_id'),
            validationCode: ValidationCode::fromString((string) $model->getAttribute('validation_code')),
            status: CertificateStatus::from((string) $model->getAttribute('status')),
            issuedAt: new DateTimeImmutable((string) $model->getAttribute('issued_at')),
            expiresAt: $expiresAt === null ? null : new DateTimeImmutable((string) $expiresAt),
            history: array_map(
                static fn (CertificateHistoryEntryModel $entry): CertificateHistoryEntry => CertificateHistoryEntry::restore(
                    CertificateStatus::from((string) $entry->getAttribute('from_status')),
                    CertificateStatus::from((string) $entry->getAttribute('to_status')),
                    new DateTimeImmutable((string) $entry->getAttribute('occurred_at')),
                    $entry->getAttribute('reason') === null ? null : (string) $entry->getAttribute('reason'),
                ),
                $historyModels,
            ),
        );
    }
}
