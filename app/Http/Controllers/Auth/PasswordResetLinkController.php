<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EnviarEnlacePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(EnviarEnlacePasswordRequest $request): RedirectResponse
    {
        $estado = Password::sendResetLink($request->only('email'));

        if (! in_array($estado, [Password::RESET_LINK_SENT, Password::INVALID_USER], true)) {
            throw ValidationException::withMessages(['email' => __($estado)]);
        }

        return back()->with('status', 'Si el correo está registrado, recibirás un enlace de recuperación.');
    }
}
