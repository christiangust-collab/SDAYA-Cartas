<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Documento;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DescargarDocumentoController extends Controller
{
    public function __invoke(Documento $documento, string $formato): BinaryFileResponse
    {
        $this->authorize('descargar', $documento);
        abort_unless(in_array($formato, ['pdf', 'docx'], true), 404);

        $ruta = $formato === 'pdf' ? $documento->archivo_pdf : $documento->archivo_docx;
        abort_unless(is_string($ruta), 404);

        $disco = $this->disco();
        abort_unless($disco->exists($ruta), 404);

        $nombre = Str::slug((string) $documento->cite).'.'.$formato;

        return response()->download($disco->path($ruta), $nombre, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function disco(): FilesystemAdapter
    {
        return Storage::disk((string) config('sdaya.documentos.disk', 'local'));
    }
}
