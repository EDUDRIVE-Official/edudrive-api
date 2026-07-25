<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Modules\Foundation\Presentation\Http\Middleware\CorrelationId;
use Modules\Foundation\Presentation\Http\Responses\ApiErrorResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CorrelationId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request, Throwable $exception): bool => $request->is('api/*')
                || $request->expectsJson(),
        );

        $exceptions->render(
            static function (
                ValidationException $exception,
                Request $request,
            ) {
                if (! $request->is('api/*') && ! $request->expectsJson()) {
                    return null;
                }

                return ApiErrorResponse::make(
                    message: 'Los datos enviados no son válidos.',
                    status: 422,
                    code: 'VALIDATION_ERROR',
                    errors: $exception->errors(),
                );
            },
        );

        $exceptions->render(
            static function (
                AuthenticationException $exception,
                Request $request,
            ) {
                if (! $request->is('api/*') && ! $request->expectsJson()) {
                    return null;
                }

                return ApiErrorResponse::make(
                    message: 'Debe autenticarse para acceder a este recurso.',
                    status: 401,
                    code: 'UNAUTHENTICATED',
                );
            },
        );

        $exceptions->render(
            static function (
                DomainException $exception,
                Request $request,
            ) {
                if (! $request->is('api/*') && ! $request->expectsJson()) {
                    return null;
                }

                return ApiErrorResponse::make(
                    message: $exception->getMessage(),
                    status: $exception->statusCode(),
                    code: $exception->errorCode(),
                );
            },
        );

        $exceptions->render(
            static function (
                NotFoundHttpException $exception,
                Request $request,
            ) {
                if (! $request->is('api/*') && ! $request->expectsJson()) {
                    return null;
                }

                return ApiErrorResponse::make(
                    message: 'El recurso solicitado no existe.',
                    status: 404,
                    code: 'RESOURCE_NOT_FOUND',
                );
            },
        );

        $exceptions->render(
            static function (
                HttpExceptionInterface $exception,
                Request $request,
            ) {
                if (! $request->is('api/*') && ! $request->expectsJson()) {
                    return null;
                }

                return ApiErrorResponse::make(
                    message: 'No fue posible procesar la solicitud.',
                    status: $exception->getStatusCode(),
                    code: 'HTTP_ERROR',
                );
            },
        );

        $exceptions->render(
            static function (
                Throwable $exception,
                Request $request,
            ) {
                if (! $request->is('api/*') && ! $request->expectsJson()) {
                    return null;
                }

                report($exception);

                return ApiErrorResponse::unexpected($exception);
            },
        );
    })
    ->create();
