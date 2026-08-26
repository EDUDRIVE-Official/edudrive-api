<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Certification\Application\Commands\IssueCertificateCommand;
use Modules\Certification\Application\Commands\RevokeCertificateCommand;
use Modules\Certification\Application\Exceptions\CertificateAlreadyExists;
use Modules\Certification\Application\Exceptions\CertificateNotFound;
use Modules\Certification\Application\Queries\GetCertificateQuery;
use Modules\Certification\Application\Queries\GetMyCertificatesQuery;
use Modules\Certification\Application\Responses\CertificateResponse;
use Modules\Certification\Application\UseCases\GetCertificateHandler;
use Modules\Certification\Application\UseCases\GetMyCertificatesHandler;
use Modules\Certification\Application\UseCases\IssueCertificateHandler;
use Modules\Certification\Application\UseCases\RevokeCertificateHandler;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Exceptions\InvalidCertificateTransition;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;

final class InMemoryCertificateRepository implements CertificateRepository
{
    /** @var array<string, Certificate> */
    public array $items = [];

    public function save(Certificate $certificate): void
    {
        $this->items[$certificate->id()->value()] = $certificate;
    }

    public function findById(CertificateId $id): ?Certificate
    {
        return $this->items[$id->value()] ?? null;
    }

    public function findByUserAndCourse(string $userId, string $courseId): ?Certificate
    {
        foreach ($this->items as $certificate) {
            if ($certificate->userId() === $userId && $certificate->courseId() === $courseId) {
                return $certificate;
            }
        }

        return null;
    }

    public function findByValidationCode(ValidationCode $validationCode): ?Certificate
    {
        foreach ($this->items as $certificate) {
            if ($certificate->validationCode()->equals($validationCode)) {
                return $certificate;
            }
        }

        return null;
    }

    /** @return list<Certificate> */
    public function allForUser(string $userId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (Certificate $certificate): bool => $certificate->userId() === $userId,
        ));
    }
}

function persistedCertificateFor(InMemoryCertificateRepository $repository, ?string $userId = null, ?string $courseId = null): Certificate
{
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $userId ?? (string) Str::uuid(),
        courseId: $courseId ?? (string) Str::uuid(),
        validationCode: ValidationCode::generate(),
    );
    $repository->save($certificate);

    return $certificate;
}

it('emite un certificado nuevo con un codigo de validacion', function (): void {
    $repository = new InMemoryCertificateRepository;
    $userId = (string) Str::uuid();
    $courseId = (string) Str::uuid();

    $response = (new IssueCertificateHandler($repository))->handle(new IssueCertificateCommand($userId, $courseId));

    expect($response)->toBeInstanceOf(CertificateResponse::class)
        ->and($response->userId)->toBe($userId)
        ->and($response->courseId)->toBe($courseId)
        ->and($response->status)->toBe('issued')
        ->and($response->validationCode)->toMatch('/^[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}-[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}-[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}$/')
        ->and($response->expiresAt)->toBeNull();
});

it('acepta una vigencia opcional al emitir', function (): void {
    $repository = new InMemoryCertificateRepository;
    $expiresAt = new DateTimeImmutable('2027-08-26T00:00:00+00:00');

    $response = (new IssueCertificateHandler($repository))->handle(new IssueCertificateCommand(
        (string) Str::uuid(),
        (string) Str::uuid(),
        $expiresAt,
    ));

    expect($response->expiresAt)->toBe($expiresAt->format(DateTimeInterface::ATOM));
});

it('rechaza emitir un segundo certificado para el mismo usuario y curso', function (): void {
    $repository = new InMemoryCertificateRepository;
    $certificate = persistedCertificateFor($repository);

    expect(fn () => (new IssueCertificateHandler($repository))->handle(new IssueCertificateCommand($certificate->userId(), $certificate->courseId())))
        ->toThrow(CertificateAlreadyExists::class);
});

it('revoca un certificado existente', function (): void {
    $repository = new InMemoryCertificateRepository;
    $certificate = persistedCertificateFor($repository);

    $response = (new RevokeCertificateHandler($repository))->handle(new RevokeCertificateCommand($certificate->id()->value(), 'Fraude'));

    expect($response->status)->toBe('revoked');
});

it('rechaza revocar un certificado inexistente', function (): void {
    $repository = new InMemoryCertificateRepository;

    expect(fn () => (new RevokeCertificateHandler($repository))->handle(new RevokeCertificateCommand((string) Str::uuid())))
        ->toThrow(CertificateNotFound::class);
});

it('propaga el rechazo de dominio al revocar dos veces', function (): void {
    $repository = new InMemoryCertificateRepository;
    $certificate = persistedCertificateFor($repository);
    (new RevokeCertificateHandler($repository))->handle(new RevokeCertificateCommand($certificate->id()->value()));

    expect(fn () => (new RevokeCertificateHandler($repository))->handle(new RevokeCertificateCommand($certificate->id()->value())))
        ->toThrow(InvalidCertificateTransition::class);
});

it('devuelve el certificado al dueno o a un tercero con permiso ampliado', function (): void {
    $repository = new InMemoryCertificateRepository;
    $certificate = persistedCertificateFor($repository);

    $own = (new GetCertificateHandler($repository))->handle(new GetCertificateQuery($certificate->id()->value(), $certificate->userId(), false));
    expect($own->id)->toBe($certificate->id()->value());

    $others = (new GetCertificateHandler($repository))->handle(new GetCertificateQuery($certificate->id()->value(), (string) Str::uuid(), true));
    expect($others->id)->toBe($certificate->id()->value());
});

it('rechaza consultar el certificado de un tercero sin permiso ampliado', function (): void {
    $repository = new InMemoryCertificateRepository;
    $certificate = persistedCertificateFor($repository);

    expect(fn () => (new GetCertificateHandler($repository))->handle(new GetCertificateQuery($certificate->id()->value(), (string) Str::uuid(), false)))
        ->toThrow(CertificateNotFound::class);
});

it('rechaza consultar un certificado inexistente', function (): void {
    $repository = new InMemoryCertificateRepository;

    expect(fn () => (new GetCertificateHandler($repository))->handle(new GetCertificateQuery((string) Str::uuid(), (string) Str::uuid(), true)))
        ->toThrow(CertificateNotFound::class);
});

it('lista todos los certificados del usuario autenticado', function (): void {
    $repository = new InMemoryCertificateRepository;
    $userId = (string) Str::uuid();
    persistedCertificateFor($repository, $userId);
    persistedCertificateFor($repository, $userId);
    persistedCertificateFor($repository);

    $responses = (new GetMyCertificatesHandler($repository))->handle(new GetMyCertificatesQuery($userId));

    expect($responses)->toHaveCount(2)
        ->and($responses[0])->toBeInstanceOf(CertificateResponse::class);
});
