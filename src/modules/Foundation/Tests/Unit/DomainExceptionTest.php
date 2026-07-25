<?php

declare(strict_types=1);

use Modules\Foundation\Domain\Exceptions\DomainException;

it('expone el código y estado HTTP del error de dominio', function (): void {
    $exception = new class extends DomainException
    {
        public function __construct()
        {
            parent::__construct(
                message: 'La operación no está permitida.',
                errorCode: 'OPERATION_NOT_ALLOWED',
                statusCode: 409,
            );
        }
    };

    expect($exception->getMessage())
        ->toBe('La operación no está permitida.')
        ->and($exception->errorCode())
        ->toBe('OPERATION_NOT_ALLOWED')
        ->and($exception->statusCode())
        ->toBe(409);
});
