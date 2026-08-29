<?php

declare(strict_types=1);

use Modules\Integration\Domain\Services\ExternalScopeAllowlist;

it('permite los alcances externos curados', function (): void {
    expect(ExternalScopeAllowlist::allows('enrollments.manage'))->toBeTrue()
        ->and(ExternalScopeAllowlist::allows('enrollments.view'))->toBeTrue()
        ->and(ExternalScopeAllowlist::allows('certifications.view'))->toBeTrue()
        ->and(ExternalScopeAllowlist::allows('road_passports.view'))->toBeTrue()
        ->and(ExternalScopeAllowlist::allows('reports.view'))->toBeTrue();
});

it('rechaza un valor que ni siquiera es un permiso valido', function (): void {
    expect(ExternalScopeAllowlist::allows('no.es.un.permiso'))->toBeFalse();
});

it('rechaza un permiso valido del sistema que no esta en la lista externa', function (): void {
    expect(ExternalScopeAllowlist::allows('system_settings.manage'))->toBeFalse()
        ->and(ExternalScopeAllowlist::allows('legal_policies.manage'))->toBeFalse()
        ->and(ExternalScopeAllowlist::allows('users.manage'))->toBeFalse()
        ->and(ExternalScopeAllowlist::allows('certifications.manage'))->toBeFalse()
        ->and(ExternalScopeAllowlist::allows('road_passports.manage'))->toBeFalse();
});
