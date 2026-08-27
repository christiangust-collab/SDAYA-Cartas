<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BuscarVerificacionRequest;
use Illuminate\Http\RedirectResponse;

final class BuscarVerificacionController extends Controller
{
    public function __invoke(BuscarVerificacionRequest $request): RedirectResponse
    {
        return redirect()->route('verificar.show', $request->validated('codigo'));
    }
}
