<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Services\DocumentoHashService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PdfPublicoController extends Controller
{
    public function __invoke(string $hash, DocumentoHashService $hashes): BinaryFileResponse
    {
        $documento = Documento::query()
            ->where('hash_verificacion', $hash)
            ->where('estado', 'emitido')
            ->firstOrFail();

        abort_unless(is_string($documento->archivo_pdf), 404);
        $disco = $this->disco();
        abort_unless($disco->exists($documento->archivo_pdf), 404);
        abort_unless($hashes->pdfEsIntegro($documento), 404);

        return response()->file($disco->path($documento->archivo_pdf), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self'",
        ]);
    }

    private function disco(): FilesystemAdapter
    {
        return Storage::disk((string) config('sdaya.documentos.disk', 'local'));
    }
}
