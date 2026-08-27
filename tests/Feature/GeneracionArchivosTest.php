<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\GeneradorArchivosDocumento;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Tipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

final class GeneracionArchivosTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_UNO_POR_UNO = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private const GIF_UNO_POR_UNO = 'R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';

    private const JPEG_UNO_POR_UNO = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wgARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD3+iiigD//2Q==';

    private const WEBP_UNO_POR_UNO = 'UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==';

    public function test_genera_word_pdf_y_qr_en_almacenamiento_privado(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('La prueba de archivos requiere Imagick.');
        }

        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        $documento = $this->crearDocumentoEmitido();

        $archivos = app(GeneradorArchivosDocumento::class)->generar($documento);

        Storage::disk('local')->assertExists($archivos->docx);
        Storage::disk('local')->assertExists($archivos->pdf);
        $this->assertStringStartsWith('%PDF', (string) Storage::disk('local')->get($archivos->pdf));
        $this->assertSame(64, strlen($archivos->hashPdf));
    }

    public function test_el_docx_tiene_encabezado_correcto_y_sin_textos_automaticos(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('La prueba de archivos requiere Imagick.');
        }

        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        $documento = $this->crearDocumentoEmitido([
            'contenido' => '<p><span class="ql-font-montserrat ql-size-14" style="color: #e60000;">Señores:</span></p>'
                .'<p style="background-color: #ffff00;">PRESIDENTE EJECUTIVO DE EJEMPLO S.R.L.</p>'
                .'<p class="ql-align-center">Presente.</p>'
                .'<p>Asunto: Solicitud de información.</p>'
                .'<p><img src="data:image/png;base64,'.self::PNG_UNO_POR_UNO.'" alt="anexo"></p>'
                .'<p><img src="data:image/webp;base64,'.self::WEBP_UNO_POR_UNO.'" alt="webp"></p>'
                .'<p><img src="data:image/gif;base64,'.self::GIF_UNO_POR_UNO.'" alt="gif"></p>'
                .'<p><img src="data:image/jpeg;base64,'.self::JPEG_UNO_POR_UNO.'" alt="jpeg"></p>',
        ]);

        $archivos = app(GeneradorArchivosDocumento::class)->generar($documento);
        $xml = $this->documentXmlDelDocx($archivos->docx);

        $this->assertStringContainsString('La Paz, 15 de agosto de 2026', $xml);
        $this->assertStringContainsString('CITE:SD-ADM-NE-2026/001', $this->quitarEspaciosXml($xml));

        $this->assertStringNotContainsString('Señor(a):', $xml);
        $this->assertStringNotContainsString('Atentamente,', $xml);

        $posicionFecha = mb_strpos($xml, 'La Paz, 15 de agosto de 2026');
        $posicionCite = mb_strpos($xml, 'SD-ADM-NE-2026/001');
        $posicionContenido = mb_strpos($xml, 'PRESIDENTE EJECUTIVO');
        $this->assertNotFalse($posicionFecha);
        $this->assertNotFalse($posicionCite);
        $this->assertNotFalse($posicionContenido);
        $this->assertTrue($posicionFecha < $posicionCite && $posicionCite < $posicionContenido);

        $this->assertStringContainsString('Montserrat', $xml);
        $this->assertStringContainsString('e60000', mb_strtolower($xml));
        $this->assertStringContainsString('ffff00', mb_strtolower($xml));

        $imagenes = $this->imagenesDelDocx($archivos->docx);
        $this->assertGreaterThanOrEqual(4, count($imagenes), 'Deben embebirse las cuatro imágenes.');
        $this->assertNull(
            collect($imagenes)->first(static fn (string $nombre): bool => preg_match('/\.(webp|gif)$/i', $nombre) === 1),
            'WebP/GIF deben convertirse a PNG.',
        );
        $this->assertTrue(
            collect($imagenes)->contains(static fn (string $nombre): bool => str_ends_with($nombre, '.png')),
            'Debe haber imágenes PNG (convertidas o originales).',
        );
    }

    public function test_la_imagen_flotante_se_posiciona_absoluta_detras_del_texto_en_docx(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('La prueba de archivos requiere Imagick.');
        }

        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        $documento = $this->crearDocumentoEmitido([
            'contenido' => '<p>Primera línea del documento.</p>'
                .'<img class="ql-flotante" src="data:image/png;base64,'.self::PNG_UNO_POR_UNO.'" style="position: absolute; left: 55%; top: 40%;" width="4" height="4" alt="firma">'
                .'<p>Última línea del documento.</p>',
        ]);

        $archivos = app(GeneradorArchivosDocumento::class)->generar($documento);
        $xml = $this->documentXmlDelDocx($archivos->docx);

        $this->assertStringContainsString('position:absolute', $xml, 'La flotante debe posicionarse absoluta en Word.');
        $this->assertStringContainsString('z-index:-2147483647', strtolower($xml), 'La flotante debe quedar detrás del texto.');
        $this->assertStringContainsString('margin-left', $xml);

        Storage::disk('local')->assertExists($archivos->pdf);
        $this->assertStringStartsWith('%PDF', (string) Storage::disk('local')->get($archivos->pdf));
    }

    public function test_la_plantilla_pdf_no_genera_textos_automaticos_y_ordena_el_encabezado(): void
    {
        $documento = $this->crearDocumentoEmitido([
            'contenido' => '<p>Señores:</p><p>Presente.</p><p>Atentamente,</p>',
        ]);

        $html = view('documentos.pdf', [
            'documento' => $documento,
            'contenidoDocumento' => $documento->contenido,
            'lugarDocumento' => 'La Paz',
            'membreteDataUri' => 'data:image/png;base64,'.self::PNG_UNO_POR_UNO,
            'qrDataUri' => 'data:image/png;base64,'.self::PNG_UNO_POR_UNO,
            'urlVerificacion' => 'https://ejemplo.test/verificar',
        ])->render();

        $this->assertStringContainsString('La Paz, 15 de agosto de 2026', $html);
        $this->assertStringContainsString('CITE: SD-ADM-NE-2026/001', $html);
        $this->assertStringContainsString('Atentamente,', $html);

        $posicionLugar = mb_strpos($html, 'La Paz, 15 de agosto de 2026');
        $posicionCite = mb_strpos($html, 'CITE:');
        $posicionContenido = mb_strpos($html, 'Señores:');
        $posicionQr = mb_strpos($html, 'Escanea para verificar');

        $this->assertTrue($posicionLugar < $posicionCite && $posicionCite < $posicionContenido && $posicionContenido < $posicionQr);
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function crearDocumentoEmitido(array $atributos = []): Documento
    {
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        return Documento::factory()->emitido()->create([
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'anio' => 2026,
            'cite' => 'SD-ADM-NE-2026/001',
            'correlativo' => 1,
            'fecha_documento' => '2026-08-15',
            'lugar' => 'La Paz',
            'contenido' => '<p>Prueba de generación <strong>institucional</strong>.</p>',
            ...$atributos,
        ])->load(['area', 'tipo']);
    }

    private function documentXmlDelDocx(string $rutaRelativa): string
    {
        $zip = new ZipArchive;
        $ruta = Storage::disk('local')->path($rutaRelativa);

        $this->assertTrue($zip->open($ruta) === true, 'El DOCX debe ser un archivo ZIP válido.');

        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertNotSame('', $xml);

        return $xml;
    }

    /** @return list<string> */
    private function imagenesDelDocx(string $rutaRelativa): array
    {
        $zip = new ZipArchive;
        $ruta = Storage::disk('local')->path($rutaRelativa);

        $this->assertTrue($zip->open($ruta) === true);

        $imagenes = [];

        for ($indice = 0; $indice < $zip->numFiles; $indice++) {
            $nombre = (string) $zip->getNameIndex($indice);

            if (str_starts_with($nombre, 'word/media/')) {
                $imagenes[] = $nombre;
            }
        }

        $zip->close();

        return $imagenes;
    }

    private function quitarEspaciosXml(string $xml): string
    {
        return (string) preg_replace('/\s+/u', '', $xml);
    }

    public function test_pdf_aplica_alineacion_izquierda_al_encabezado(): void
    {
        $documento = $this->crearDocumentoEmitido([
            'contenido'             => '<p>Contenido de prueba.</p>',
            'alineacion_encabezado' => 'left',
        ]);

        $html = view('documentos.pdf', [
            'documento'             => $documento,
            'contenidoDocumento'    => $documento->contenido,
            'lugarDocumento'        => 'La Paz',
            'membreteDataUri'       => 'data:image/png;base64,'.self::PNG_UNO_POR_UNO,
            'qrDataUri'             => 'data:image/png;base64,'.self::PNG_UNO_POR_UNO,
            'urlVerificacion'       => 'https://ejemplo.test/verificar',
            'alineacionEncabezado'  => 'left',
        ])->render();

        $this->assertStringContainsString('.meta { margin-bottom: 26px; text-align: left; }', $html);
    }

    public function test_pdf_aplica_alineacion_centro_al_encabezado(): void
    {
        $documento = $this->crearDocumentoEmitido([
            'contenido'             => '<p>Contenido de prueba centro.</p>',
            'alineacion_encabezado' => 'center',
        ]);

        $html = view('documentos.pdf', [
            'documento'             => $documento,
            'contenidoDocumento'    => $documento->contenido,
            'lugarDocumento'        => 'La Paz',
            'membreteDataUri'       => 'data:image/png;base64,'.self::PNG_UNO_POR_UNO,
            'qrDataUri'             => 'data:image/png;base64,'.self::PNG_UNO_POR_UNO,
            'urlVerificacion'       => 'https://ejemplo.test/verificar',
            'alineacionEncabezado'  => 'center',
        ])->render();

        $this->assertStringContainsString('.meta { margin-bottom: 26px; text-align: center; }', $html);
    }

    public function test_pdf_usa_alineacion_derecha_por_defecto(): void
    {
        $documento = $this->crearDocumentoEmitido([
            'contenido' => '<p>Prueba default derecha.</p>',
        ]);

        $html = view('documentos.pdf', [
            'documento'            => $documento,
            'contenidoDocumento'   => $documento->contenido,
            'lugarDocumento'       => 'La Paz',
            'membreteDataUri'      => 'data:image/png;base64,'.self::PNG_UNO_POR_UNO,
            'qrDataUri'            => 'data:image/png;base64,'.self::PNG_UNO_POR_UNO,
            'urlVerificacion'      => 'https://ejemplo.test/verificar',
            // Sin alineacionEncabezado → blade usa 'right' como fallback
        ])->render();

        $this->assertStringContainsString('.meta { margin-bottom: 26px; text-align: right; }', $html);
    }

    public function test_docx_aplica_alineacion_izquierda(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('La prueba de archivos requiere Imagick.');
        }

        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        $documento = $this->crearDocumentoEmitido([
            'contenido'             => '<p>Contenido con alineación izquierda.</p>',
            'alineacion_encabezado' => 'left',
        ]);

        $archivos = app(GeneradorArchivosDocumento::class)->generar($documento);
        $xml = $this->documentXmlDelDocx($archivos->docx);

        // Verificar que el XML del DOCX contenga la alineación izquierda (jc w:val="left")
        $this->assertStringContainsString('La Paz, 15 de agosto de 2026', $xml);
        $this->assertStringContainsString('SD-ADM-NE-2026/001', $xml);
        // PHPWord usa w:jc w:val="left" para alineación izquierda
        $this->assertStringContainsString('w:val="left"', $xml);
    }

    public function test_pdf_y_docx_procesan_widgets_sdaya_emision(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('La prueba de archivos requiere Imagick.');
        }

        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        $contenidoConWidgets = '<div class="sdaya-meta sdaya-align-right" data-sdaya-meta="true">'
            .'<p class="sdaya-meta-fecha" data-sdaya-widget="fecha">La Paz, 15 de agosto de 2026</p>'
            .'<p class="sdaya-meta-cite" data-sdaya-widget="cite">CITE: Pendiente</p>'
            .'</div>'
            .'<p>Cuerpo con tabla y widget QR.</p>'
            .'<figure class="table"><table><tbody><tr><td>Tabla datos</td></tr></tbody></table></figure>'
            .'<div class="sdaya-qr sdaya-align-center" data-sdaya-qr="true">'
            .'<p class="sdaya-qr-placeholder">[QR_INSTITUCIONAL_SDAYA]</p>'
            .'</div>';

        $documento = $this->crearDocumentoEmitido([
            'contenido' => $contenidoConWidgets,
            'cite' => 'SD-ADM-NE-2026/099',
        ]);

        $archivos = app(GeneradorArchivosDocumento::class)->generar($documento);

        Storage::disk('local')->assertExists($archivos->docx);
        Storage::disk('local')->assertExists($archivos->pdf);

        $xml = $this->documentXmlDelDocx($archivos->docx);
        $this->assertStringContainsString('SD-ADM-NE-2026/099', $xml);
        $this->assertStringContainsString('Tabla datos', $xml);
    }
}
