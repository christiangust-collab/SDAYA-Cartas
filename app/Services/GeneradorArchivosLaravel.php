<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GeneradorArchivosDocumento;
use App\Data\ArchivosGenerados;
use App\Models\Documento;
use Barryvdh\DomPDF\Facade\Pdf;
use DOMDocument;
use DOMElement;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

final readonly class GeneradorArchivosLaravel implements GeneradorArchivosDocumento
{
    /** Área útil de la página carta en puntos (tras márgenes del DOCX: Sup 3cm, Inf 3cm, Izq 3cm, Der 2.5cm). */
    private const ANCHO_UTIL_PT = 456.1;

    private const ALTO_UTIL_PT = 621.9;

    public function __construct(
        private HtmlParaDocumento $html,
        private NormalizadorImagenes $imagenes,
    ) {}

    public function generar(Documento $documento): ArchivosGenerados
    {
        $disco = $this->disco();
        $directorio = $this->directorio($documento);

        try {
            if (! extension_loaded('imagick')) {
                throw new RuntimeException('La extensión Imagick es necesaria para generar el QR en PNG.');
            }

            if (! $disco->makeDirectory($directorio)) {
                throw new RuntimeException('No se pudo preparar el directorio privado del documento.');
            }

            $urlVerificacion = route('verificar.show', $documento->hash_verificacion);
            $qr = (string) QrCode::format('png')
                ->size(300)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($urlVerificacion);

            $rutaQr = "{$directorio}/qr.png";
            $rutaPdf = "{$directorio}/documento.pdf";
            $rutaDocx = "{$directorio}/documento.docx";

            if (! $disco->put($rutaQr, $qr)) {
                throw new RuntimeException('No se pudo guardar el código QR.');
            }

            $contenidoNormalizado = $this->imagenes->normalizar((string) $documento->contenido);

            // Reemplazar placeholders de widgets institucionales en el contenido si existen
            $qrDataUri = 'data:image/png;base64,'.base64_encode($qr);
            if (str_contains($contenidoNormalizado, 'data-sdaya-qr')) {
                $contenidoNormalizado = str_replace(
                    '[QR_INSTITUCIONAL_SDAYA]',
                    '<img src="'.$qrDataUri.'" alt="QR SDAYA" style="width: 90px; height: 90px;"><p style="font-size: 7.5pt; color: #4766a9; margin-top: 4px;">Escanea para verificar autenticidad</p><p style="font-size: 6.5pt; color: #64748b; font-family: monospace;">'.$documento->hash_verificacion.'</p>',
                    $contenidoNormalizado,
                );
            }

            if (str_contains($contenidoNormalizado, 'data-sdaya-meta')) {
                $fechaTexto = $this->lugar($documento).', '.$documento->fecha_documento->locale('es')->translatedFormat('d \d\e F \d\e Y');
                $citeTexto = 'CITE: '.$documento->cite;
                $contenidoNormalizado = (string) preg_replace(
                    '/<p class="sdaya-meta-fecha"[^>]*>.*?<\/p>/is',
                    '<p class="sdaya-meta-fecha" data-sdaya-widget="fecha">'.$fechaTexto.'</p>',
                    $contenidoNormalizado,
                );
                $contenidoNormalizado = (string) preg_replace(
                    '/<p class="sdaya-meta-cite"[^>]*>.*?<\/p>/is',
                    '<p class="sdaya-meta-cite" data-sdaya-widget="cite">'.$citeTexto.'</p>',
                    $contenidoNormalizado,
                );
            }

            $separado = $this->separarFlotantes($contenidoNormalizado);

            $cacheFuentes = $this->directorioCacheFuentes();
            if (! is_dir($cacheFuentes)) {
                @mkdir($cacheFuentes, 0775, true);
            }

            $pdf = Pdf::loadView('documentos.pdf', [
                'documento' => $documento,
                'datosEmpresa' => $documento->datosEmpresa(),
                'contenidoDocumento' => $contenidoNormalizado,
                'lugarDocumento' => $this->lugar($documento),
                'membreteDataUri' => $this->resolverMembreteDataUri($documento),
                'qrDataUri' => $qrDataUri,
                'urlVerificacion' => $urlVerificacion,
                'alineacionEncabezado' => $this->alineacionCss($documento),
            ])
                ->setPaper('letter')
                ->setOption('isRemoteEnabled', false)
                ->setOption('defaultFont', 'DejaVu Sans')
                ->setOptions([
                    'fontDir' => $cacheFuentes,
                    'fontCache' => $cacheFuentes,
                ]);

            $contenidoPdf = $pdf->output();

            if (! $disco->put($rutaPdf, $contenidoPdf)) {
                throw new RuntimeException('No se pudo guardar el archivo PDF.');
            }

            $this->generarDocx(
                documento: $documento,
                destino: $disco->path($rutaDocx),
                qr: $disco->path($rutaQr),
                urlVerificacion: $urlVerificacion,
                contenidoHtml: $separado['html'],
                flotantes: $separado['flotantes'],
            );

            return new ArchivosGenerados(
                docx: $rutaDocx,
                pdf: $rutaPdf,
                hashPdf: hash('sha256', $contenidoPdf),
            );
        } catch (Throwable $exception) {
            $disco->deleteDirectory($directorio);

            throw $exception;
        }
    }

    public function renderizarPdf(Documento $documento, bool $sinMembrete = false, bool $esBorrador = false): string
    {
        $contenidoNormalizado = $this->imagenes->normalizar((string) $documento->contenido);

        $urlVerificacion = $documento->hash_verificacion
            ? route('verificar.show', $documento->hash_verificacion)
            : url('/');

        $qrDataUri = '';
        if ($documento->hash_verificacion) {
            $qr = (string) QrCode::format('png')
                ->size(300)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($urlVerificacion);
            $qrDataUri = 'data:image/png;base64,'.base64_encode($qr);
        }

        if (str_contains($contenidoNormalizado, 'data-sdaya-qr') && $qrDataUri) {
            $contenidoNormalizado = str_replace(
                '[QR_INSTITUCIONAL_SDAYA]',
                '<img src="'.$qrDataUri.'" alt="QR SDAYA" style="width: 90px; height: 90px;"><p style="font-size: 7.5pt; color: #4766a9; margin-top: 4px;">Escanea para verificar autenticidad</p><p style="font-size: 6.5pt; color: #64748b; font-family: monospace;">'.$documento->hash_verificacion.'</p>',
                $contenidoNormalizado,
            );
        }

        if (str_contains($contenidoNormalizado, 'data-sdaya-meta')) {
            $fechaTexto = $this->lugar($documento).', '.$documento->fecha_documento->locale('es')->translatedFormat('d \d\e F \d\e Y');
            $citeTexto = $documento->cite ? 'CITE: '.$documento->cite : 'CITE: (Borrador)';
            $contenidoNormalizado = (string) preg_replace(
                '/<p class="sdaya-meta-fecha"[^>]*>.*?<\/p>/is',
                '<p class="sdaya-meta-fecha" data-sdaya-widget="fecha">'.$fechaTexto.'</p>',
                $contenidoNormalizado,
            );
            $contenidoNormalizado = (string) preg_replace(
                '/<p class="sdaya-meta-cite"[^>]*>.*?<\/p>/is',
                '<p class="sdaya-meta-cite" data-sdaya-widget="cite">'.$citeTexto.'</p>',
                $contenidoNormalizado,
            );
        }

        $cacheFuentes = $this->directorioCacheFuentes();
        if (! is_dir($cacheFuentes)) {
            @mkdir($cacheFuentes, 0775, true);
        }

        $pdf = Pdf::loadView('documentos.pdf', [
            'documento' => $documento,
            'datosEmpresa' => $documento->datosEmpresa(),
            'contenidoDocumento' => $contenidoNormalizado,
            'lugarDocumento' => $this->lugar($documento),
            'membreteDataUri' => $sinMembrete ? null : $this->resolverMembreteDataUri($documento),
            'qrDataUri' => $qrDataUri,
            'urlVerificacion' => $urlVerificacion,
            'alineacionEncabezado' => $this->alineacionCss($documento),
            'sinMembrete' => $sinMembrete,
            'esBorrador' => $esBorrador,
        ])
            ->setPaper('letter')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOptions([
                'fontDir' => $cacheFuentes,
                'fontCache' => $cacheFuentes,
            ]);

        return $pdf->output();
    }

    public function generarDocxBorrador(Documento $documento): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'sdaya_docx_').'.docx';
        $contenidoNormalizado = $this->imagenes->normalizar((string) $documento->contenido);
        $separado = $this->separarFlotantes($contenidoNormalizado);
        $urlVerificacion = url('/');

        $qrTemp = tempnam(sys_get_temp_dir(), 'sdaya_qr_').'.png';
        $qrContent = (string) QrCode::format('png')->size(300)->margin(1)->generate($urlVerificacion);
        file_put_contents($qrTemp, $qrContent);

        try {
            $this->generarDocx(
                documento: $documento,
                destino: $tempPath,
                qr: $qrTemp,
                urlVerificacion: $urlVerificacion,
                contenidoHtml: $separado['html'],
                flotantes: $separado['flotantes'],
            );
        } finally {
            @unlink($qrTemp);
        }

        return $tempPath;
    }

    private function resolverMembreteRuta(Documento $documento): ?string
    {
        $datosEmpresa = $documento->datosEmpresa();
        $logo = $datosEmpresa['logo_documentos'] ?? null;

        if (filled($logo)) {
            $disco = Storage::disk(config('sdaya.documentos.disk', 'local'));
            if ($disco->exists((string) $logo)) {
                $rutaAbs = $disco->path((string) $logo);
                if (is_file($rutaAbs)) {
                    return $rutaAbs;
                }
            }
        }

        return null;
    }

    private function resolverMembreteDataUri(Documento $documento): ?string
    {
        $ruta = $this->resolverMembreteRuta($documento);
        if (! $ruta) {
            return null;
        }

        return $this->imagenDataUri($ruta);
    }

    private function generarDocx(
        Documento $documento,
        string $destino,
        string $qr,
        string $urlVerificacion,
        string $contenidoHtml = '',
        array $flotantes = [],
    ): void {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Montserrat');
        $phpWord->setDefaultFontSize(11);

        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 13, 'color' => '293D61']);

        $margenes = $documento->margenes();
        $marginTop = (int) round($margenes['top'] * 567);
        $marginRight = (int) round($margenes['right'] * 567);
        $marginBottom = (int) round($margenes['bottom'] * 567);
        $marginLeft = (int) round($margenes['left'] * 567);

        $seccion = $phpWord->addSection([
            'pageSizeW' => 12240,
            'pageSizeH' => 15840,
            'marginTop' => $marginTop,
            'marginRight' => $marginRight,
            'marginBottom' => $marginBottom,
            'marginLeft' => $marginLeft,
            'headerHeight' => 0,
            'footerHeight' => 0,
        ]);

        $rutaMembrete = $this->resolverMembreteRuta($documento);
        if ($rutaMembrete && is_file($rutaMembrete)) {
            $encabezado = $seccion->addHeader();
            $encabezado->addWatermark($rutaMembrete, [
                'width' => 612,
                'height' => 792,
                'marginLeft' => -0.05,
                'marginTop' => -0.05,
                'wrappingStyle' => 'behind',
            ]);
        }

        $alineacionWord = $this->alineacionWord($documento);

        if (! str_contains($contenidoHtml, 'data-sdaya-meta')) {
            $seccion->addText($this->lugar($documento).', '.$documento->fecha_documento->locale('es')->translatedFormat('d \d\e F \d\e Y'), [], ['alignment' => $alineacionWord]);
            $seccion->addText('CITE: '.$documento->cite, ['bold' => true, 'color' => '293D61'], ['alignment' => $alineacionWord]);
            $seccion->addTextBreak();
        }
        $patronSalto = '/<div[^>]*class="[^"]*(?:page-break|sdaya-page-break)[^"]*"[^>]*>.*?<\/div>|<div[^>]*style="[^"]*page-break-(?:after|before):\s*always[^"]*"[^>]*>.*?<\/div>/si';
        $bloques = preg_split($patronSalto, $contenidoHtml);

        if ($bloques !== false && count($bloques) > 1) {
            foreach ($bloques as $indice => $bloque) {
                $bloqueProcesado = $this->html->paraWord($bloque);
                if (trim($bloqueProcesado) !== '') {
                    Html::addHtml($seccion, $bloqueProcesado, false, false);
                }
                if ($indice < count($bloques) - 1) {
                    $seccion->addPageBreak();
                }
            }
        } else {
            Html::addHtml($seccion, $this->html->paraWord($contenidoHtml), false, false);
        }

        foreach ($flotantes as $flotante) {
            $this->agregarImagenFlotante($seccion, $flotante);
        }

        $pieFirma = $documento->pieFirma();
        if ($pieFirma && (! empty($pieFirma['nombre']) || ! empty($pieFirma['cargo']))) {
            $alineacionPieWord = match ($documento->alineacion_pie_firma ?? $documento->alineacion_encabezado ?? 'right') {
                'left' => 'left',
                'center' => 'center',
                default => 'right',
            };
            $seccion->addTextBreak(2);

            if (! empty($pieFirma['firma_digital'])) {
                $disco = Storage::disk(config('sdaya.documentos.disk', 'local'));
                if ($disco->exists((string) $pieFirma['firma_digital'])) {
                    $rutaAbsolutaFirma = $disco->path((string) $pieFirma['firma_digital']);
                    if (is_file($rutaAbsolutaFirma)) {
                        $seccion->addImage($rutaAbsolutaFirma, [
                            'height' => 50,
                            'alignment' => $alineacionPieWord,
                        ]);
                    }
                }
            }

            $estiloParrafo = ['alignment' => $alineacionPieWord, 'spaceAfter' => 20];
            $seccion->addText($pieFirma['nombre'], ['bold' => true, 'size' => 11, 'color' => '0F172A'], $estiloParrafo);
            if (! empty($pieFirma['cargo'])) {
                $seccion->addText(mb_strtoupper((string) $pieFirma['cargo'], 'UTF-8'), ['bold' => true, 'size' => 9.5, 'color' => '0F172A'], $estiloParrafo);
            }
            if (! empty($pieFirma['empresa'])) {
                $seccion->addText(mb_strtoupper((string) $pieFirma['empresa'], 'UTF-8'), ['bold' => true, 'size' => 8.5, 'color' => '0F172A'], $estiloParrafo);
            }
            if (! empty($pieFirma['telefono'])) {
                $textoRun = $seccion->addTextRun($estiloParrafo);
                $textoRun->addText('móvil: ', ['bold' => true, 'size' => 9, 'color' => '0F172A']);
                $textoRun->addText((string) $pieFirma['telefono'], ['size' => 9, 'color' => '0F172A']);
            }
            if (! empty($pieFirma['correo'])) {
                $textoRun = $seccion->addTextRun($estiloParrafo);
                $textoRun->addText('email: ', ['bold' => true, 'size' => 9, 'color' => '0F172A']);
                $textoRun->addText((string) $pieFirma['correo'], ['size' => 9, 'color' => '0F172A']);
            }
        }

        if (! str_contains($contenidoHtml, 'data-sdaya-qr')) {
            $seccion->addTextBreak(2);
            $seccion->addImage($qr, [
                'width' => 92,
                'height' => 92,
                'alignment' => 'center',
            ], false, 'Código QR de verificación SDAYA');
            $seccion->addText(
                'Escanea para verificar la autenticidad del documento',
                ['size' => 8, 'color' => '4766A9'],
                ['alignment' => 'center'],
            );
            $seccion->addLink(
                $urlVerificacion,
                'Verificación en línea',
                ['size' => 8, 'color' => '437DBF', 'underline' => 'single'],
                ['alignment' => 'center'],
            );
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($destino);
    }

    private function directorio(Documento $documento): string
    {
        $base = trim((string) config('sdaya.documentos.directorio', 'documentos'), '/');
        $cite = Str::slug(str_replace('/', '-', (string) $documento->cite));

        return "{$base}/{$documento->anio}/{$cite}";
    }

    private function lugar(Documento $documento): string
    {
        $lugar = trim((string) $documento->lugar);

        return $lugar !== '' ? $lugar : (string) config('sdaya.marca.lugar', 'La Paz');
    }

    /**
     * Convierte el valor de alineacion_encabezado del documento al valor
     * CSS correspondiente para uso en la plantilla PDF (DomPDF).
     */
    private function alineacionCss(Documento $documento): string
    {
        return match (trim((string) $documento->alineacion_encabezado)) {
            'left'   => 'left',
            'center' => 'center',
            default  => 'right',
        };
    }

    /**
     * Convierte el valor de alineacion_encabezado del documento al valor
     * que entiende PHPWord para el parámetro 'alignment' de addText().
     */
    private function alineacionWord(Documento $documento): string
    {
        return match (trim((string) $documento->alineacion_encabezado)) {
            'left'   => 'left',
            'center' => 'center',
            default  => 'right',
        };
    }

    /**
     * Separa las imágenes flotantes (ql-flotante con posición en %) del HTML:
     * el PDF las conserva como position:absolute, mientras que para Word se
     * extraen del flujo y se colocan programáticamente con PHPWord.
     *
     * @return array{html: string, flotantes: list<array{fuente: string, x: float, y: float, ancho: ?int, alto: ?int}>}
     */
    private function separarFlotantes(string $html): array
    {
        if (! str_contains($html, 'ql-flotante')) {
            return ['html' => $html, 'flotantes' => []];
        }

        $documento = new DOMDocument('1.0', 'UTF-8');
        $previo = libxml_use_internal_errors(true);

        $documento->loadHTML(
            '<?xml encoding="UTF-8"><div id="sdaya-flot-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previo);

        $raiz = $documento->getElementById('sdaya-flot-root');

        if (! $raiz instanceof DOMElement) {
            return ['html' => $html, 'flotantes' => []];
        }

        $flotantes = [];

        foreach (iterator_to_array($raiz->getElementsByTagName('img')) as $imagen) {
            if (! str_contains((string) $imagen->getAttribute('class'), 'ql-flotante')) {
                continue;
            }

            preg_match('/left:\s*(-?[\d.]+)%/i', (string) $imagen->getAttribute('style'), $izquierda);
            preg_match('/top:\s*(-?[\d.]+)%/i', (string) $imagen->getAttribute('style'), $arriba);
            $ancho = $imagen->getAttribute('width');
            $alto = $imagen->getAttribute('height');

            $flotantes[] = [
                'fuente' => (string) $imagen->getAttribute('src'),
                'x' => isset($izquierda[1]) ? (float) $izquierda[1] : 50.0,
                'y' => isset($arriba[1]) ? (float) $arriba[1] : 40.0,
                'ancho' => $ancho !== '' ? (int) $ancho : null,
                'alto' => $alto !== '' ? (int) $alto : null,
            ];

            $imagen->parentNode?->removeChild($imagen);
        }

        $salida = '';

        foreach ($raiz->childNodes as $nodo) {
            $salida .= $documento->saveHTML($nodo);
        }

        return ['html' => trim($salida), 'flotantes' => $flotantes];
    }

    /**
     * Coloca una imagen flotante en el DOCX con posicionamiento absoluto
     * detrás del texto. La posición en porcentaje se convierte a puntos
     * sobre el área útil de la página carta.
     *
     * @param  array{fuente: string, x: float, y: float, ancho: ?int, alto: ?int}  $flotante
     */
    private function agregarImagenFlotante(Section $seccion, array $flotante): void
    {
        $binario = base64_decode(
            (string) preg_replace('#^data:image/[a-z0-9.+-]+;base64,#i', '', $flotante['fuente']),
            true,
        );

        if ($binario === false || $binario === '') {
            return;
        }

        $temporal = tempnam(sys_get_temp_dir(), 'sdaya_flot_');

        if ($temporal === false) {
            return;
        }

        try {
            file_put_contents($temporal, $binario);

            $estilo = [
                'positioning' => 'absolute',
                'wrap' => 'behind',
                'hPosRelTo' => 'margin',
                'vPosRelTo' => 'margin',
                'left' => round($flotante['x'] * self::ANCHO_UTIL_PT / 100, 1),
                'top' => round($flotante['y'] * self::ALTO_UTIL_PT / 100, 1),
            ];

            if ($flotante['ancho'] !== null && $flotante['ancho'] > 0) {
                $estilo['width'] = round($flotante['ancho'] * 0.75, 1);
            }

            if ($flotante['alto'] !== null && $flotante['alto'] > 0) {
                $estilo['height'] = round($flotante['alto'] * 0.75, 1);
            }

            $seccion->addImage($temporal, $estilo);
        } finally {
            @unlink($temporal);
        }
    }

    private function directorioCacheFuentes(): string
    {
        return storage_path('fonts');
    }

    private function imagenDataUri(?string $ruta): ?string
    {
        if (! $ruta || ! is_file($ruta)) {
            return null;
        }

        $contenido = file_get_contents($ruta);

        if ($contenido === false) {
            return null;
        }

        $mime = mime_content_type($ruta) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contenido);
    }

    private function disco(): FilesystemAdapter
    {
        return Storage::disk((string) config('sdaya.documentos.disk', 'local'));
    }
}
