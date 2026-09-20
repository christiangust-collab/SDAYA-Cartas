<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\GeneradorArchivosDocumento;
use App\Data\ArchivosGenerados;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Tipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests para la importación de archivos DOCX.
 */
final class ImportarDocxTest extends TestCase
{
    use RefreshDatabase;

    /**
     * DOCX mínimo válido: un ZIP que contiene los archivos requeridos por
     * la especificación OOXML. Generado con PHPWord para garantizar que el
     * servicio real pueda leerlo en pruebas de integración.
     */
    private function crearDocxMinimo(string $contenidoTexto = 'Texto de prueba'): string
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $seccion = $phpWord->addSection();
        $seccion->addText($contenidoTexto);

        $rutaTemporal = sys_get_temp_dir().'/sdaya_test_'.uniqid().'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($rutaTemporal);

        return $rutaTemporal;
    }

    public function test_rechaza_peticion_sin_archivo(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->postJson(route('documentos.importar-docx'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['archivo']);
    }

    public function test_rechaza_archivo_que_no_es_docx(): void
    {
        $editor = User::factory()->editor()->create();

        $rutaTxt = sys_get_temp_dir().'/prueba.txt';
        file_put_contents($rutaTxt, 'no soy un docx');

        $response = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            ['archivo' => new \Illuminate\Http\UploadedFile($rutaTxt, 'prueba.txt', 'text/plain', null, true)],
        );

        @unlink($rutaTxt);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['archivo']);
    }

    public function test_importa_docx_con_texto_simple_y_devuelve_html(): void
    {
        $editor = User::factory()->editor()->create();
        $rutaDocx = $this->crearDocxMinimo('Este es el contenido importado.');

        $response = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            ['archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'prueba.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true)],
        );

        @unlink($rutaDocx);

        $response->assertOk()
            ->assertJsonStructure(['html']);

        $html = $response->json('html');
        $this->assertStringContainsString('Este es el contenido importado.', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_importa_docx_con_texto_en_negrita(): void
    {
        $editor = User::factory()->editor()->create();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $seccion = $phpWord->addSection();
        $run = $seccion->addTextRun();
        $run->addText('Texto normal ');
        $run->addText('Texto en negrita', ['bold' => true]);

        $rutaDocx = sys_get_temp_dir().'/sdaya_test_negrita_'.uniqid().'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($rutaDocx);

        $response = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            ['archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'negrita.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true)],
        );

        @unlink($rutaDocx);

        $response->assertOk();
        $html = $response->json('html');
        $this->assertStringContainsString('<strong>', $html);
        $this->assertStringContainsString('Texto en negrita', $html);
    }

    public function test_importar_docx_requiere_autenticacion(): void
    {
        $this->postJson(route('documentos.importar-docx'))
            ->assertUnauthorized();
    }

    public function test_el_html_importado_puede_usarse_como_contenido_de_documento(): void
    {
        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        $this->app->instance(GeneradorArchivosDocumento::class, new class implements GeneradorArchivosDocumento
        {
            public function generar(Documento $documento): ArchivosGenerados
            {
                $pdf = 'pdf-importado-'.$documento->cite;
                Storage::disk('local')->put('pruebas/documento.pdf', $pdf);
                Storage::disk('local')->put('pruebas/documento.docx', 'docx-prueba');

                return new ArchivosGenerados(
                    docx: 'pruebas/documento.docx',
                    pdf: 'pruebas/documento.pdf',
                    hashPdf: hash('sha256', $pdf),
                );
            }
        });

        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        // Primero importar el DOCX para obtener el HTML
        $rutaDocx = $this->crearDocxMinimo('Contenido importado desde Word.');
        $respuestaImport = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            ['archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'doc.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true)],
        );
        @unlink($rutaDocx);

        $htmlImportado = $respuestaImport->json('html');
        $this->assertNotEmpty($htmlImportado);

        // Luego crear el documento con ese contenido (simulando lo que haría el usuario)
        $respuesta = $this->actingAs($editor)->post(route('documentos.store'), [
            'area_id'                => $area->id,
            'tipo_id'                => $tipo->id,
            'fecha_documento'        => '2026-08-21',
            'alineacion_encabezado'  => 'left',
            'contenido'              => $htmlImportado,
            'accion'                 => 'guardar',
        ]);

        $documento = Documento::query()->sole();
        $respuesta->assertRedirect(route('documentos.show', $documento));
        $this->assertStringContainsString('Contenido importado desde Word.', $documento->contenido);
        $this->assertSame('left', $documento->alineacion_encabezado);
    }

    public function test_importa_docx_con_alineaciones_derecha_centro_y_justificada(): void
    {
        $editor = User::factory()->editor()->create();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $seccion = $phpWord->addSection();
        $seccion->addText('La Paz, 15 de julio de 2026', [], ['alignment' => 'right']);
        $seccion->addText('CITE: SD-ADM-NE-2026/003', ['bold' => true], ['alignment' => 'right']);
        $seccion->addText('TITULO CENTRADO', ['bold' => true], ['alignment' => 'center']);
        $seccion->addText('Párrafo justificado con texto largo.', [], ['alignment' => 'both']);

        $rutaDocx = sys_get_temp_dir().'/sdaya_test_alineaciones_'.uniqid().'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($rutaDocx);

        $response = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            ['archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'alineaciones.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true)],
        );

        @unlink($rutaDocx);

        $response->assertOk();
        $html = $response->json('html');
        $this->assertStringContainsString('text-align: right', $html);
        $this->assertStringContainsString('La Paz, 15 de julio de 2026', $html);
        $this->assertStringContainsString('CITE: SD-ADM-NE-2026/003', $html);
        $this->assertStringContainsString('text-align: center', $html);
        $this->assertStringContainsString('TITULO CENTRADO', $html);
        $this->assertStringContainsString('text-align: justify', $html);
    }

    public function test_importa_docx_con_formatos_enriquecidos_cursiva_subrayado_color_y_tamanio(): void
    {
        $editor = User::factory()->editor()->create();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $seccion = $phpWord->addSection();
        $run = $seccion->addTextRun();
        $run->addText('Texto cursiva ', ['italic' => true]);
        $run->addText('Texto subrayado ', ['underline' => 'single']);
        $run->addText('Texto azul ', ['color' => '293D61']);
        $run->addText('Texto grande ', ['size' => 16]);

        $rutaDocx = sys_get_temp_dir().'/sdaya_test_estilos_'.uniqid().'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($rutaDocx);

        $response = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            ['archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'estilos.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true)],
        );

        @unlink($rutaDocx);

        $response->assertOk();
        $html = $response->json('html');
        $this->assertStringContainsString('<em>Texto cursiva', $html);
        $this->assertStringContainsString('<u>Texto subrayado', $html);
        $this->assertStringContainsString('color: #293D61', $html);
        $this->assertStringContainsString('font-size: 16pt', $html);
    }

    public function test_importa_docx_con_tablas_y_celdas(): void
    {
        $editor = User::factory()->editor()->create();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $seccion = $phpWord->addSection();
        $table = $seccion->addTable();
        $row1 = $table->addRow();
        $row1->addCell(2000)->addText('Encabezado 1', ['bold' => true]);
        $row1->addCell(2000)->addText('Encabezado 2', ['bold' => true]);
        $row2 = $table->addRow();
        $row2->addCell(2000)->addText('Dato 1');
        $row2->addCell(2000)->addText('Dato 2');

        $rutaDocx = sys_get_temp_dir().'/sdaya_test_tabla_'.uniqid().'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($rutaDocx);

        $response = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            ['archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'tabla.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true)],
        );

        @unlink($rutaDocx);

        $response->assertOk();
        $html = $response->json('html');
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('Encabezado 1', $html);
        $this->assertStringContainsString('Dato 1', $html);
    }

    public function test_importa_docx_con_listas_agrupadas(): void
    {
        $editor = User::factory()->editor()->create();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $seccion = $phpWord->addSection();
        $seccion->addListItem('Primer elemento');
        $seccion->addListItem('Segundo elemento');
        $seccion->addListItem('Tercer elemento');

        $rutaDocx = sys_get_temp_dir().'/sdaya_test_lista_'.uniqid().'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($rutaDocx);

        $response = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            ['archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'lista.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true)],
        );

        @unlink($rutaDocx);

        $response->assertOk();
        $html = $response->json('html');
        $this->assertStringContainsString('<li', $html);
        $this->assertStringContainsString('Primer elemento', $html);
        $this->assertStringContainsString('Segundo elemento', $html);
    }

    public function test_importa_docx_con_imagenes_embebidas(): void
    {
        $editor = User::factory()->editor()->create();

        // 1x1 base64 png
        $png1x1 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $rutaImg = sys_get_temp_dir().'/test_img_'.uniqid().'.png';
        file_put_contents($rutaImg, $png1x1);

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $seccion = $phpWord->addSection();
        $seccion->addText('Texto antes de la imagen');
        $seccion->addImage($rutaImg, ['width' => 100, 'height' => 100]);
        $seccion->addText('Texto despues de la imagen');

        $rutaDocx = sys_get_temp_dir().'/sdaya_test_img_'.uniqid().'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($rutaDocx);
        @unlink($rutaImg);

        $response = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            ['archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'imagen.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true)],
        );

        @unlink($rutaDocx);

        $response->assertOk();
        $html = $response->json('html');
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('data:image/png;base64', $html);
    }

    public function test_importa_docx_con_contenido_completo_combinado(): void
    {
        $editor = User::factory()->editor()->create();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $seccion = $phpWord->addSection();
        $seccion->addText('La Paz, 15 de julio de 2026', [], ['alignment' => 'right']);
        $seccion->addText('CITE: SD-ADM-NE-2026/003', ['bold' => true], ['alignment' => 'right']);
        $seccion->addText('Señores:', ['bold' => true]);
        $seccion->addText('PRESIDENTE EJECUTIVO DE EJEMPLO', ['bold' => true]);
        $seccion->addText('Presente.-', ['italic' => true]);

        $seccion->addText('REF.: SOLICITUD DE INFORMACIÓN TÉCNICA', ['bold' => true, 'underline' => 'single'], ['alignment' => 'center']);
        $seccion->addText('De nuestra mayor consideración:');
        $seccion->addText('Mediante la presente nos dirigimos a su autoridad para solicitar la siguiente documentación:', [], ['alignment' => 'both']);

        $seccion->addListItem('Informe técnico de avance de obras.');
        $seccion->addListItem('Planilla de seguimiento presupuestario.');

        $tabla = $seccion->addTable();
        $r1 = $tabla->addRow();
        $r1->addCell(3000)->addText('Ítem', ['bold' => true]);
        $r1->addCell(3000)->addText('Descripción', ['bold' => true]);
        $r2 = $tabla->addRow();
        $r2->addCell(3000)->addText('01');
        $r2->addCell(3000)->addText('Revisión preliminar');

        $rutaDocx = sys_get_temp_dir().'/sdaya_test_combinado_'.uniqid().'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($rutaDocx);

        $response = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            ['archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'combinado.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true)],
        );

        @unlink($rutaDocx);

        $response->assertOk();
        $html = $response->json('html');

        $this->assertStringContainsString('text-align: right', $html);
        $this->assertStringContainsString('La Paz, 15 de julio de 2026', $html);
        $this->assertStringContainsString('CITE: SD-ADM-NE-2026/003', $html);
        $this->assertStringContainsString('text-align: center', $html);
        $this->assertStringContainsString('SOLICITUD DE INFORMACIÓN TÉCNICA', $html);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('<li', $html);
    }

    public function test_importa_docx_y_extrae_metadatos_destinatario_y_asunto(): void
    {
        $editor = User::factory()->editor()->create();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $seccion = $phpWord->addSection();
        $seccion->addText('Señor:');
        $seccion->addText('Ing. Juan Pérez');
        $seccion->addText('DIRECTOR GENERAL DE AGUAS');
        $seccion->addText('Presente.-');
        $seccion->addText('REF: SOLICITUD DE REUNIÓN DE COORDINACIÓN');
        $seccion->addText('De nuestra mayor consideración, le escribimos para coordinar la fecha...');

        $rutaDocx = sys_get_temp_dir().'/sdaya_test_meta_'.uniqid().'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($rutaDocx);

        $response = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            ['archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'con_meta.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true)],
        );

        @unlink($rutaDocx);

        $response->assertOk();
        $this->assertStringContainsString('Ing. Juan Pérez', $response->json('destinatario'));
        $this->assertStringContainsString('SOLICITUD DE REUNIÓN DE COORDINACIÓN', $response->json('asunto'));
    }

    public function test_importa_docx_con_salto_de_pagina(): void
    {
        $editor = User::factory()->editor()->create();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $seccion = $phpWord->addSection();
        $seccion->addText('Página 1 del documento');
        $seccion->addPageBreak();
        $seccion->addText('Página 2 del documento');

        $rutaDocx = sys_get_temp_dir().'/sdaya_test_pb_'.uniqid().'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($rutaDocx);

        $response = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            ['archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'con_pb.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true)],
        );

        @unlink($rutaDocx);

        $response->assertOk();
        $html = $response->json('html');
        $this->assertStringContainsString('page-break', $html);
        $this->assertStringContainsString('Página 1 del documento', $html);
        $this->assertStringContainsString('Página 2 del documento', $html);
    }

    public function test_importa_docx_con_firma_y_permite_omitirla(): void
    {
        $editor = User::factory()->editor()->create();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $seccion = $phpWord->addSection();
        $seccion->addText('Cuerpo del comunicado oficial.');
        $seccion->addText('Sin otro particular, nos despedimos atentamente.');

        // Bloque de firma
        $tabla = $seccion->addTable();
        $r = $tabla->addRow();
        $c = $r->addCell(4000);
        $c->addText('Lic. Carlos Morales');
        $c->addText('GERENTE GENERAL');
        $c->addText('móvil: 77712345');

        $rutaDocx = sys_get_temp_dir().'/sdaya_test_firma_'.uniqid().'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($rutaDocx);

        // Prueba con omitir_firma = true (por defecto)
        $responseOmitir = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            [
                'archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'con_firma.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true),
                'omitir_firma' => '1',
            ],
        );

        $responseOmitir->assertOk();
        $this->assertTrue($responseOmitir->json('tiene_firma_detectada'));
        $this->assertStringNotContainsString('GERENTE GENERAL', $responseOmitir->json('html'));
        $this->assertStringContainsString('GERENTE GENERAL', $responseOmitir->json('html_completo'));

        // Prueba con omitir_firma = false
        $responseIncluir = $this->actingAs($editor)->postJson(
            route('documentos.importar-docx'),
            [
                'archivo' => new \Illuminate\Http\UploadedFile($rutaDocx, 'con_firma.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true),
                'omitir_firma' => '0',
            ],
        );

        @unlink($rutaDocx);

        $responseIncluir->assertOk();
        $this->assertStringContainsString('GERENTE GENERAL', $responseIncluir->json('html'));
    }
}
