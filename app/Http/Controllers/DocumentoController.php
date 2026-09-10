<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoDocumento;
use App\Http\Requests\FiltrarDocumentosRequest;
use App\Http\Requests\GuardarDocumentoRequest;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Empresa;
use App\Models\Tipo;
use App\Models\User;
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
        $usuario = $request->user();
        $filtros = $request->validated();

        $queryBase = Documento::query();

        if ($usuario && ! $usuario->esAdministrador()) {
            $queryBase->where(function ($q) use ($usuario) {
                $q->where('estado', '!=', EstadoDocumento::BORRADOR)
                  ->orWhere('emitido_por', $usuario->id);
            });
        }

        $documentos = (clone $queryBase)
            ->conRelaciones()
            ->filtrar($filtros)
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('documentos.index', [
            'documentos' => $documentos,
            'areas' => Area::query()->orderBy('codigo')->get(),
            'tipos' => Tipo::query()->orderBy('codigo')->get(),
            'empresas' => Empresa::query()->activas()->orderBy('nombre')->get(),
            'anios' => Documento::query()->select('anio')->distinct()->orderByDesc('anio')->pluck('anio'),
            'estados' => EstadoDocumento::cases(),
            'filtros' => $filtros,
            'resumen' => [
                'total' => (clone $queryBase)->count(),
                'borradores' => (clone $queryBase)->where('estado', EstadoDocumento::BORRADOR)->count(),
                'emitidos' => (clone $queryBase)->where('estado', EstadoDocumento::EMITIDO)->count(),
                'anulados' => (clone $queryBase)->where('estado', EstadoDocumento::ANULADO)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Documento::class);

        return view('documentos.create', $this->catalogosActivos(request()->user()));
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
            ->with('success', 'Borrador guardado correctamente. Revisa la vista previa antes de finalizar la carta.');
    }

    public function show(Documento $documento): View
    {
        $this->authorize('view', $documento);

        return view('documentos.show', [
            'documento' => $documento->load(['area', 'tipo', 'emisor', 'firmante', 'empresa', 'eventos.usuario']),
        ]);
    }

    public function preview(Documento $documento): View
    {
        $this->authorize('view', $documento);

        return view('documentos.show', [
            'documento' => $documento->load(['area', 'tipo', 'emisor', 'firmante', 'empresa', 'eventos.usuario']),
            'esVistaPrevia' => true,
        ]);
    }

    public function edit(Documento $documento): View
    {
        $this->authorize('update', $documento);

        return view('documentos.edit', [
            ...$this->catalogosActivos(request()->user()),
            'documento' => $documento->load(['area', 'tipo', 'firmante', 'empresa']),
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
            ->with('success', 'Borrador actualizado correctamente. Revisa la vista previa antes de finalizar la carta.');
    }

    /** @return array{areas: mixed, tipos: mixed, firmantes: mixed, empresas: mixed} */
    private function catalogosActivos(?User $usuario = null): array
    {
        $usuario = $usuario ?? auth()->user();
        $firmantesQuery = User::query()
            ->with('empresaInstitucion')
            ->whereIn('role', [\App\Enums\RolUsuario::ADMIN, \App\Enums\RolUsuario::EDITOR])
            ->orderBy('name');

        if ($usuario && ! $usuario->esAdministrador() && $usuario->empresa_id) {
            $firmantesQuery->where(function ($q) use ($usuario) {
                $q->where('empresa_id', $usuario->empresa_id)
                  ->orWhere('id', $usuario->id);
            });
        }

        return [
            'areas' => Area::query()->activas()->orderBy('codigo')->get(),
            'tipos' => Tipo::query()->activos()->orderBy('codigo')->get(),
            'firmantes' => $firmantesQuery->get(),
            'empresas' => Empresa::query()->activas()->orderBy('nombre')->get(),
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
