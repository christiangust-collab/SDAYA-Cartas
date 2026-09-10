<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GuardarAjustesRequest;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class AjustesController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Empresa::class);

        $empresa = Empresa::actual();
        $todasEmpresas = Empresa::query()->orderBy('nombre')->get();

        return view('ajustes.index', [
            'empresa' => $empresa,
            'todasEmpresas' => $todasEmpresas,
        ]);
    }

    public function update(GuardarAjustesRequest $request): RedirectResponse
    {
        $validados = $request->validated();
        $disk = Storage::disk(config('sdaya.documentos.disk', 'local'));

        // Determinar qué empresa se está editando
        $empresaId = $request->input('empresa_activa_id');
        $empresa = $empresaId ? Empresa::query()->find($empresaId) : null;

        if ($empresa === null) {
            $empresa = Empresa::query()->where('es_predeterminada', true)->first()
                ?? Empresa::query()->first();
        }

        if ($empresa === null) {
            $empresa = new Empresa;
            $empresa->activo = true;
        }

        // 1. Manejo de Logo principal
        $rutaLogo = $empresa->logo;
        if ($request->boolean('eliminar_logo')) {
            if ($rutaLogo && $disk->exists($rutaLogo)) {
                $disk->delete($rutaLogo);
            }
            $rutaLogo = null;
        }
        if ($request->hasFile('logo')) {
            if ($rutaLogo && $disk->exists($rutaLogo)) {
                $disk->delete($rutaLogo);
            }
            $archivo = $request->file('logo');
            $ext = $archivo->getClientOriginalExtension() ?: 'png';
            $nombre = sprintf('identidad/logo_%s.%s', time(), $ext);
            $disk->put($nombre, file_get_contents($archivo->getRealPath()));
            $rutaLogo = $nombre;
        }

        // 2. Manejo de Favicon
        $rutaFavicon = $empresa->favicon;
        if ($request->boolean('eliminar_favicon')) {
            if ($rutaFavicon && $disk->exists($rutaFavicon)) {
                $disk->delete($rutaFavicon);
            }
            $rutaFavicon = null;
        }
        if ($request->hasFile('favicon')) {
            if ($rutaFavicon && $disk->exists($rutaFavicon)) {
                $disk->delete($rutaFavicon);
            }
            $archivo = $request->file('favicon');
            $ext = $archivo->getClientOriginalExtension() ?: 'ico';
            $nombre = sprintf('identidad/favicon_%s.%s', time(), $ext);
            $disk->put($nombre, file_get_contents($archivo->getRealPath()));
            $rutaFavicon = $nombre;
        }

        // 3. Manejo de Logo / Membrete para Documentos
        $rutaLogoDoc = $empresa->logo_documentos;
        if ($request->boolean('eliminar_logo_documentos')) {
            if ($rutaLogoDoc && $disk->exists($rutaLogoDoc)) {
                $disk->delete($rutaLogoDoc);
            }
            $rutaLogoDoc = null;
        }
        if ($request->hasFile('logo_documentos')) {
            if ($rutaLogoDoc && $disk->exists($rutaLogoDoc)) {
                $disk->delete($rutaLogoDoc);
            }
            $archivo = $request->file('logo_documentos');
            $ext = strtolower((string) ($archivo->getClientOriginalExtension() ?: 'png'));
            $mime = (string) $archivo->getMimeType();

            if ($ext === 'pdf' || $mime === 'application/pdf') {
                $contenidoPng = $this->convertirPdfAPng($archivo->getRealPath());
                $nombre = sprintf('identidad/membrete_%s.png', time());
                $disk->put($nombre, $contenidoPng);
                $rutaLogoDoc = $nombre;
            } else {
                $nombre = sprintf('identidad/membrete_%s.%s', time(), $ext);
                $disk->put($nombre, file_get_contents($archivo->getRealPath()));
                $rutaLogoDoc = $nombre;
            }
        }

        // Asegurar que solo esta empresa sea la predeterminada
        Empresa::query()->where('id', '!=', $empresa->id)->update(['es_predeterminada' => false]);

        $empresa->fill([
            'nombre' => trim((string) $validados['nombre']),
            'nombre_comercial' => filled($validados['nombre_comercial'] ?? null) ? trim((string) $validados['nombre_comercial']) : null,
            'nombre_aplicacion' => trim((string) $validados['nombre_aplicacion']),
            'nit' => filled($validados['nit'] ?? null) ? trim((string) $validados['nit']) : null,
            'direccion' => filled($validados['direccion'] ?? null) ? trim((string) $validados['direccion']) : null,
            'telefono' => filled($validados['telefono'] ?? null) ? trim((string) $validados['telefono']) : null,
            'correo' => filled($validados['correo'] ?? null) ? trim((string) $validados['correo']) : null,
            'sitio_web' => filled($validados['sitio_web'] ?? null) ? trim((string) $validados['sitio_web']) : null,
            'color_principal' => strtolower(trim((string) $validados['color_principal'])),
            'color_secundario' => strtolower(trim((string) $validados['color_secundario'])),
            'logo' => $rutaLogo,
            'favicon' => $rutaFavicon,
            'logo_documentos' => $rutaLogoDoc,
            'es_predeterminada' => true,
            'activo' => true,
        ]);

        $empresa->save();

        return redirect()->route('ajustes.index')
            ->with('success', 'Configuración institucional e identidad visual actualizadas correctamente.');
    }

    public function archivo(string $tipo): Response
    {
        $empresa = Empresa::actual();
        $disk = Storage::disk(config('sdaya.documentos.disk', 'local'));

        $ruta = match ($tipo) {
            'logo' => $empresa->logo,
            'favicon' => $empresa->favicon,
            'documentos' => $empresa->logo_documentos,
            default => null,
        };

        if (! $ruta || ! $disk->exists($ruta)) {
            abort(404);
        }

        $contenido = $disk->get($ruta);
        $mime = $disk->mimeType($ruta) ?: 'image/png';

        return response($contenido, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function convertirPdfAPng(string $rutaPdf): string
    {
        $salidaPrefijo = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'membrete_conv_' . uniqid();

        // 1. Intentar con pdftoppm (poppler-utils) - rápido y alta resolución (300 DPI)
        $comando = sprintf('pdftoppm -png -r 300 -f 1 -l 1 %s %s 2>&1', escapeshellarg($rutaPdf), escapeshellarg($salidaPrefijo));
        @exec($comando, $salidaExec, $codigoSalida);

        if ($codigoSalida === 0) {
            $archivos = glob($salidaPrefijo . '*.png');
            if (! empty($archivos) && file_exists($archivos[0])) {
                $contenido = file_get_contents($archivos[0]);
                foreach ($archivos as $archivoTemporal) {
                    @unlink($archivoTemporal);
                }
                if ($contenido !== false && strlen($contenido) > 0) {
                    return $contenido;
                }
            }
        }

        // 2. Intentar con Imagick si está disponible y soporta PDF
        if (class_exists(\Imagick::class)) {
            try {
                $imagick = new \Imagick();
                $imagick->setResolution(300, 300);
                $imagick->readImage($rutaPdf . '[0]');
                $imagick->setImageFormat('png');
                $contenido = $imagick->getImageBlob();
                $imagick->clear();
                $imagick->destroy();
                if ($contenido !== false && strlen($contenido) > 0) {
                    return $contenido;
                }
            } catch (\Throwable) {
                // Imagick no pudo procesar el PDF
            }
        }

        // 3. Respaldo seguro con GD si el entorno no dispone de herramientas CLI
        $ancho = 2032;
        $alto = 2646;
        $imagen = imagecreatetruecolor($ancho, $alto);
        $blanco = (int) imagecolorallocate($imagen, 255, 255, 255);
        imagefill($imagen, 0, 0, $blanco);

        ob_start();
        imagepng($imagen);
        $fallbackPng = (string) ob_get_clean();
        imagedestroy($imagen);

        return $fallbackPng;
    }
}