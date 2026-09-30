<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Identity\Application\Commands\RequestPasswordResetCommand;
use Modules\Identity\Application\Commands\ResetPasswordCommand;
use Modules\Identity\Application\UseCases\RequestPasswordResetUseCase;
use Modules\Identity\Application\UseCases\ResetPasswordUseCase;
use Modules\Identity\Domain\Exceptions\InvalidPasswordResetToken;
use Modules\Identity\Presentation\Http\Requests\ForgotPasswordRequest;
use Modules\Identity\Presentation\Http\Requests\ResetPasswordRequest;

final class PasswordResetWebController extends Controller
{
    public function __construct(
        private readonly RequestPasswordResetUseCase $requestReset,
        private readonly ResetPasswordUseCase $resetPassword,
    ) {}

    public function createRequest(): View
    {
        return view('auth.forgot-password', [
            'usesLocalMailbox' => config('mail.delivery.mode') !== 'external',
        ]);
    }

    public function sendLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $this->requestReset->execute(new RequestPasswordResetCommand(
            email: (string) $request->string('email'),
        ));

        $status = config('mail.delivery.mode') === 'external'
            ? 'Si el correo corresponde a una cuenta, recibirás un enlace seguro para definir tu contraseña.'
            : 'Solicitud generada en modo de pruebas. Si la cuenta existe, el enlace quedó en el buzón local; no fue enviado a una dirección externa.';

        return back()->with('status', $status);
    }

    public function createReset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        try {
            $this->resetPassword->execute(new ResetPasswordCommand(
                email: (string) $request->string('email'),
                token: (string) $request->string('token'),
                newPassword: (string) $request->string('password'),
            ));
        } catch (InvalidPasswordResetToken) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['token' => 'El enlace ya fue utilizado, expiró o no es válido. Solicitá uno nuevo.']);
        }

        return redirect()->route('login')->with(
            'passwordResetStatus',
            'Tu contraseña quedó definida. Ya podés ingresar con tu cuenta.',
        );
    }
}
