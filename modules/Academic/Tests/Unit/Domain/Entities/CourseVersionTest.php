<?php

declare(strict_types=1);

use Modules\Academic\Domain\Entities\CourseVersion;
use Modules\Academic\Domain\Enums\CourseVersionStatus;
use Modules\Academic\Domain\ValueObjects\CourseId;

it('crea una version publicada con numero secuencial y snapshot canonico', function (): void {
    $snapshot = [
        'id' => '01981a64-8300-7b1d-b442-764ea7f915c0',
        'code' => 'EDU-001',
        'title' => 'Introduccion a la seguridad vial',
        'modules' => [],
    ];
    $publishedAt = new DateTimeImmutable('2026-08-10T08:00:00+00:00');

    $version = CourseVersion::create(
        id: '01981a64-8300-7b1d-b442-764ea7f92001',
        courseId: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        versionNumber: 1,
        snapshot: $snapshot,
        publishedAt: $publishedAt,
    );

    expect($version->id())->toBe('01981a64-8300-7b1d-b442-764ea7f92001')
        ->and($version->courseId()->value())->toBe('01981a64-8300-7b1d-b442-764ea7f915c0')
        ->and($version->versionNumber())->toBe(1)
        ->and($version->status())->toBe(CourseVersionStatus::Published)
        ->and($version->snapshot())->toBe($snapshot)
        ->and($version->publishedAt())->toBe($publishedAt);
});

it('restaura una version archivada', function (): void {
    $snapshot = ['id' => '01981a64-8300-7b1d-b442-764ea7f915c0'];
    $archivedAt = new DateTimeImmutable('2026-08-11T08:00:00+00:00');

    $version = CourseVersion::restore(
        id: '01981a64-8300-7b1d-b442-764ea7f92001',
        courseId: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        versionNumber: 2,
        status: CourseVersionStatus::Archived,
        snapshot: $snapshot,
        publishedAt: new DateTimeImmutable('2026-08-10T08:00:00+00:00'),
        archivedAt: $archivedAt,
    );

    expect($version->status())->toBe(CourseVersionStatus::Archived)
        ->and($version->archivedAt())->toBe($archivedAt);
});

it('rechaza un numero de version menor a uno', function (): void {
    CourseVersion::create(
        id: '01981a64-8300-7b1d-b442-764ea7f92001',
        courseId: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        versionNumber: 0,
        snapshot: [],
        publishedAt: new DateTimeImmutable('2026-08-10T08:00:00+00:00'),
    );
})->throws(InvalidArgumentException::class);
