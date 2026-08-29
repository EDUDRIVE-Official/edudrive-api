<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Jobs;

use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Analytics\Domain\Enums\AnalyticsReportType;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Throwable;

final class GenerateAnalyticsReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $asyncJobId,
        public readonly string $reportType,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(
        AsyncJobRepository $jobs,
        EnrollmentRepository $enrollments,
        CertificateRepository $certificates,
        UserRepository $users,
    ): void {
        $job = $jobs->findById(AsyncJobId::fromString($this->asyncJobId));
        if ($job === null) {
            return;
        }

        $job->start(new DateTimeImmutable('now'));
        $jobs->save($job);

        $type = AnalyticsReportType::from($this->reportType);

        $result = match ($type) {
            AnalyticsReportType::EnrollmentsSummary => $this->enrollmentsSummary($enrollments),
            AnalyticsReportType::CertificationsSummary => $this->certificationsSummary($certificates),
            AnalyticsReportType::UsersSummary => $this->usersSummary($users),
        };

        $job->complete($result, new DateTimeImmutable('now'));
        $jobs->save($job);
    }

    /** @return array<string, mixed> */
    private function enrollmentsSummary(EnrollmentRepository $enrollments): array
    {
        $all = $enrollments->all();

        return [
            'total' => count($all),
            'by_status' => array_count_values(array_map(
                static fn (Enrollment $enrollment): string => $enrollment->status()->value,
                $all,
            )),
        ];
    }

    /** @return array<string, mixed> */
    private function certificationsSummary(CertificateRepository $certificates): array
    {
        $all = $certificates->all();

        return [
            'total' => count($all),
            'by_status' => array_count_values(array_map(
                static fn (Certificate $certificate): string => $certificate->status()->value,
                $all,
            )),
        ];
    }

    /** @return array<string, mixed> */
    private function usersSummary(UserRepository $users): array
    {
        $all = $users->all();

        return [
            'total' => count($all),
            'by_status' => array_count_values(array_map(
                static fn (User $user): string => $user->status()->value,
                $all,
            )),
        ];
    }

    public function failed(?Throwable $exception): void
    {
        $jobs = app(AsyncJobRepository::class);
        $job = $jobs->findById(AsyncJobId::fromString($this->asyncJobId));
        if ($job === null) {
            return;
        }

        $job->fail($exception?->getMessage() ?? 'Error desconocido al generar el reporte de analitica.', new DateTimeImmutable('now'));
        $jobs->save($job);

        Log::warning('Fallo la generacion asincrona de un reporte de analitica.', [
            'async_job_id' => $this->asyncJobId,
            'report_type' => $this->reportType,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
