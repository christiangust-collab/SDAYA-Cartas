<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoDocumento;
use App\Http\Requests\FiltrarDocumentosRequest;
use App\Http\Requests\GuardarDocumentoRequest;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Tipo;
use App\Services\EmitirDocumentoService;
use App\Services\GuardarDocumentoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

final class DocumentoController extends Controller
{
    public function index(FiltrarDocumentosRequest $request): View
    {
        $this->authorize('viewAny', Documento::class);
        $filtros = $request->validated();

        return view('documentos.index', [
            'documentos' => Documento::query()
                ->conRelaciones()
                ->filtrar($filtros)
                ->latest('updated_at')
                ->paginate(15)
                ->withQueryString(),
            'areas' => Area::query()->orderBy('codigo')->get(),
            'tipos' => Tipo::query()->orderBy('codigo')->get(),
            'anios' => Documento::query()->select('anio')->distinct()->orderByDesc('anio')->pluck('anio'),
            'estados' => EstadoDocumento::cases(),
            'filtros' => $filtros,
            'resumen' => [
                'total' => Documento::query()->count(),
                'borradores' => Documento::query()->where('estado', EstadoDocumento::BORRADOR)->count(),
                'emitidos' => Documento::query()->where('estado', EstadoDocumento::EMITIDO)->count(),
                'anulados' => Documento::query()->where('estado', EstadoDocumento::ANULADO)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Documento::class);

        return view('documentos.create', $this->catalogosActivos());
    }

    public function store(
        GuardarDocumentoRequest $request,
        GuardarDocumentoService $guardar,
        EmitirDocumentoService $emitir,
    ): RedirectResponse {
        $datos = $request->validated();
        $accion = $datos['accion'];
        unset($datos['accion']);

        $documento = $guardar->crear($datos, $request->user());

        if ($accion === 'emitir') {
            return $this->emitirTrasGuardar($documento, $request->user(), $emitir);
        }

        return redirect()->route('documentos.show', $documento)
            ->with('success', 'Borrador guardado correctamente.');
    }

    public function show(Documento $documento): View
    {
        $this->authorize('view', $documento);

        return view('documentos.show', [
            'documento' => $documento->load(['area', 'tipo', 'emisor', 'eventos.usuario']),
        ]);
    }

    public function edit(Documento $documento): View
    {
        $this->authorize('update', $documento);

        return view('documentos.edit', [
            ...$this->catalogosActivos(),
            'documento' => $documento->load(['area', 'tipo']),
        ]);
    }

    public function update(
        GuardarDocumentoRequest $request,
        Documento $documento,
        GuardarDocumentoService $guardar,
        EmitirDocumentoService $emitir,
    ): RedirectResponse {
        $datos = $request->validated();
        $accion = $datos['accion'];
        unset($datos['accion']);

        $documento = $guardar->actualizar($documento, $datos, $request->user());

        if ($accion === 'emitir') {
            return $this->emitirTrasGuardar($documento, $request->user(), $emitir);
        }

        return redirect()->route('documentos.show', $documento)
            ->with('success', 'Borrador actualizado correctamente.');
    }

    /** @return array{areas: mixed, tipos: mixed} */
    private function catalogosActivos(): array
    {
        return [
            'areas' => Area::query()->activas()->orderBy('codigo')->get(),
            'tipos' => Tipo::query()->activos()->orderBy('codigo')->get(),
        ];
    }

    private function emitirTrasGuardar(
        Documento $documento,
        \App\Models\User $usuario,
        EmitirDocumentoService $emitir,
    ): RedirectResponse {
        try {
            $emitido = $emitir->emitir($documento, $usuario);

            return redirect()->route('documentos.show', $emitido)
                ->with('success', 'Documento emitido y archivos generados correctamente.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('documentos.edit', $documento)
                ->with('error', 'El borrador fue guardado, pero no se pudo emitir. No se consumió ningún correlativo; revisa el registro de la aplicación.');
        }
    }
}
