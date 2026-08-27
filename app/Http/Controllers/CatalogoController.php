<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Tipo;
use Illuminate\View\View;

final class CatalogoController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('viewAny', Area::class);

        return view('catalogos.index', [
            'areas' => Area::query()->withExists('documentosEmitidos')->orderByDesc('activo')->orderBy('codigo')->get(),
            'tipos' => Tipo::query()->withExists('documentosEmitidos')->orderByDesc('activo')->orderBy('codigo')->get(),
        ]);
    }
}
