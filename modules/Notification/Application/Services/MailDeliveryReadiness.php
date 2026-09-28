<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkTransportFactory;

final class MailDeliveryReadiness
{
    /**
     * @return array{
     *     ready: bool,
     *     completed: int,
     *     total: int,
     *     uses_local_mailbox: bool,
     *     local_mailbox_url: string,
     *     checks: list<array{key: string, label: string, passed: bool, guidance: string}>
     * }
     */
    public function status(): array
    {
        $appUrl = (string) config('app.url');
        $appHost = parse_url($appUrl, PHP_URL_HOST);
        $fromAddress = (string) config('mail.from.address');

        $checks = [
            [
                'key' => 'transport',
                'label' => 'Transporte Postmark instalado',
                'passed' => class_exists(PostmarkTransportFactory::class),
                'guidance' => 'Instalar symfony/postmark-mailer en el proyecto.',
            ],
            [
                'key' => 'mailer',
                'label' => 'Postmark seleccionado como proveedor',
                'passed' => config('mail.default') === 'postmark',
                'guidance' => 'Configurar MAIL_MAILER=postmark.',
            ],
            [
                'key' => 'token',
                'label' => 'Token del servidor configurado',
                'passed' => filled(config('services.postmark.key')),
                'guidance' => 'Guardar POSTMARK_API_KEY de forma segura en el entorno; nunca en el repositorio.',
            ],
            [
                'key' => 'sender',
                'label' => 'Remitente institucional definido',
                'passed' => filter_var($fromAddress, FILTER_VALIDATE_EMAIL) !== false
                    && ! str_ends_with($fromAddress, '@example.com'),
                'guidance' => 'Verificar el dominio y configurar MAIL_FROM_ADDRESS.',
            ],
            [
                'key' => 'public_url',
                'label' => 'URL pública segura para los enlaces',
                'passed' => str_starts_with($appUrl, 'https://')
                    && is_string($appHost)
                    && ! in_array($appHost, ['localhost', '127.0.0.1'], true),
                'guidance' => 'Configurar APP_URL con el dominio HTTPS público de EduDrive.',
            ],
            [
                'key' => 'delivery_mode',
                'label' => 'Entrega externa habilitada explícitamente',
                'passed' => config('mail.delivery.mode') === 'external',
                'guidance' => 'Cambiar MAIL_DELIVERY_MODE=external solo después de validar los puntos anteriores.',
            ],
        ];

        $completed = count(array_filter($checks, static fn (array $check): bool => $check['passed']));

        return [
            'ready' => $completed === count($checks),
            'completed' => $completed,
            'total' => count($checks),
            'uses_local_mailbox' => config('mail.delivery.mode') !== 'external',
            'local_mailbox_url' => (string) config('mail.delivery.local_inbox_url'),
            'checks' => $checks,
        ];
    }
}
