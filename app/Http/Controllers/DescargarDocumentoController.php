<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Services\GeneradorArchivosLaravel;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class DescargarDocumentoController extends Controller
{
    public function __invoke(
        Request $request,
        Documento $documento,
        string $formato,
        GeneradorArchivosLaravel $generador,
    ): SymfonyResponse {
        $this->authorize('descargar', $documento);
        abort_unless(in_array($formato, ['pdf', 'docx'], true), 404);

        $sinMembrete = $request->boolean('preimpreso') || $request->boolean('sin_membrete');

        if ($documento->estaBorrador() || ($formato === 'pdf' && $sinMembrete)) {
            if ($formato === 'pdf') {
                $esBorrador = $documento->estaBorrador();
                $pdfBytes = $generador->renderizarPdf($documento, sinMembrete: $sinMembrete, esBorrador: $esBorrador);
                $nombreArchivo = $esBorrador
                    ? 'Borrador-'.$documento->id.($sinMembrete ? '-preimpreso' : '').'.pdf'
                    : Str::slug((string) $documento->cite).'-preimpreso.pdf';

                $disposition = $request->boolean('ver') ? 'inline' : 'attachment';

                return response($pdfBytes, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => "{$disposition}; filename=\"{$nombreArchivo}\"",
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }

            $tempDocx = $generador->generarDocxBorrador($documento);
            $nombreDocx = 'Borrador-'.$documento->id.'.docx';

            return response()->download($tempDocx, $nombreDocx, [
                'X-Content-Type-Options' => 'nosniff',
            ])->deleteFileAfterSend(true);
        }

        $ruta = $formato === 'pdf' ? $documento->archivo_pdf : $documento->archivo_docx;
        abort_unless(is_string($ruta), 404);

        $disco = $this->disco();
        abort_unless($disco->exists($ruta), 404);

        $nombre = Str::slug((string) $documento->cite).'.'.$formato;

        if ($formato === 'pdf' && $request->boolean('ver')) {
            return response()->file($disco->path($ruta), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$nombre}\"",
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response()->download($disco->path($ruta), $nombre, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function disco(): FilesystemAdapter
    {
        return Storage::disk((string) config('sdaya.documentos.disk', 'local'));
    }
}
