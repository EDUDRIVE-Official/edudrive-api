<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Identity\Application\Commands\LoginUserCommand;
use Modules\Identity\Application\UseCases\LoginUserUseCase;
use Modules\Identity\Domain\Exceptions\InvalidCredentials;
use Modules\Identity\Domain\Exceptions\UserCannotAuthenticate;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Identity\Presentation\Http\Requests\LoginWebRequest;

final class LoginWebController extends Controller
{
    public function __construct(
        private readonly LoginUserUseCase $useCase,
        private readonly PermissionChecker $permissionChecker,
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginWebRequest $request): RedirectResponse
    {
        try {
            $response = $this->useCase->execute(
                new LoginUserCommand(
                    email: (string) $request->string('email'),
                    password: (string) $request->string('password'),
                    tokenName: 'web',
                ),
            );
        } catch (InvalidCredentials|UserCannotAuthenticate) {
            return back()
                ->withInput($request->only('email'))
                ->with('loginError', 'El correo o la contraseña no son válidos.');
        }

        $user = UserModel::query()->findOrFail($response->userId);

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        $isAdministrator = $this->permissionChecker->userHasPermission(
            $response->userId,
            Permission::ManageUsers,
        );

        return redirect($isAdministrator ? '/organizations' : '/mi-perfil');
    }
}
