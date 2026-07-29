<?php

declare(strict_types=1);

use Modules\Identity\Domain\Exceptions\InvalidEmail;
use Modules\Identity\Domain\ValueObjects\Email;

it('normaliza un correo electrónico', function (): void {
    $email = Email::fromString('  USUARIO@EDUDRIVE.CR  ');

    expect($email->value())
        ->toBe('usuario@edudrive.cr')
        ->and((string) $email)
        ->toBe('usuario@edudrive.cr');
});

it('compara correos por su valor normalizado', function (): void {
    $firstEmail = Email::fromString('usuario@edudrive.cr');
    $secondEmail = Email::fromString('USUARIO@EDUDRIVE.CR');

    expect($firstEmail->equals($secondEmail))->toBeTrue();
});

it('rechaza un correo electrónico inválido', function (): void {
    Email::fromString('correo-invalido');
})->throws(InvalidEmail::class);
