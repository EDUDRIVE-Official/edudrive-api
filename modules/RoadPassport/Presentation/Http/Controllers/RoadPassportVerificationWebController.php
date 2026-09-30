<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Presentation\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\RoadPassport\Application\Services\RoadPassportVerificationCode;
use Modules\RoadPassport\Domain\Enums\EvidenceType;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\Services\RoadPassportTrustCalculator;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

final class RoadPassportVerificationWebController
{
    public function __invoke(
        Request $request,
        RoadPassportVerificationCode $codes,
        RoadPassportRepository $passports,
    ): View {
        $code = is_string($request->query('code')) ? trim($request->query('code')) : null;
        $passport = null;
        $notFound = false;

        if ($code !== null && $code !== '') {
            $payload = $codes->payloadFrom($code);
            $roadPassport = $payload === null ? null : $passports->findById(RoadPassportId::fromString($payload['id']));
            if ($roadPassport === null || $roadPassport->verificationVersion() !== $payload['version']) {
                $notFound = true;
            } else {
                $evidence = $roadPassport->evidence();
                $verifiedAt = Carbon::now((string) config('app.timezone', 'America/Costa_Rica'));
                $latestEvidenceAt = array_reduce(
                    $evidence,
                    static fn (?DateTimeImmutable $latest, $item): DateTimeImmutable => $latest === null || $item->occurredAt > $latest ? $item->occurredAt : $latest,
                );
                $passport = [
                    'status' => $roadPassport->status()->value,
                    'level' => $roadPassport->level(),
                    'issued_at' => Carbon::instance($roadPassport->issuedAt())->settings(['locale' => 'es'])->translatedFormat('j \\d\\e F \\d\\e Y'),
                    'trust_score' => (new RoadPassportTrustCalculator)->calculate($roadPassport, $verifiedAt->toDateTimeImmutable()),
                    'latest_evidence_at' => $latestEvidenceAt === null
                        ? 'Sin evidencias registradas'
                        : Carbon::instance($latestEvidenceAt)->timezone((string) config('app.timezone'))->settings(['locale' => 'es'])->translatedFormat('j \\d\\e F \\d\\e Y, g:i a'),
                    'verified_at' => $verifiedAt->settings(['locale' => 'es'])->translatedFormat('j \\d\\e F \\d\\e Y, g:i:s a'),
                    'digital_count' => count(array_filter($evidence, static fn ($item): bool => in_array($item->type, [EvidenceType::LessonCompleted, EvidenceType::CourseCompleted, EvidenceType::ExamPassed], true))),
                    'observed_practice_count' => count(array_filter($evidence, static fn ($item): bool => $item->type === EvidenceType::GuidedPracticeObserved)),
                    'reflection_count' => count(array_filter($evidence, static fn ($item): bool => $item->type === EvidenceType::StudentReflection)),
                ];
            }
        }

        return view('road-passport.verify', compact('code', 'passport', 'notFound'));
    }
}
