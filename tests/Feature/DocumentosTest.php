<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\GeneradorArchivosDocumento;
use App\Data\ArchivosGenerados;
use App\Enums\EstadoDocumento;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Tipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DocumentosTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_guarda_un_borrador_sanitizado_sin_consumir_correlativo(): void
    {
        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        $response = $this->actingAs($editor)->post(route('documentos.store'), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-08-15',
            'asunto' => 'Notificación de prueba',
            'destinatario' => 'Cliente de prueba',
            'contenido' => '<p onclick="alert(1)">Texto <strong>válido</strong></p><script>alert(1)</script>',
            'accion' => 'guardar',
        ]);

        $documento = Documento::query()->sole();
        $response->assertRedirect(route('documentos.show', $documento));
        $this->assertNull($documento->cite);
        $this->assertNull($documento->correlativo);
        $this->assertSame(EstadoDocumento::BORRADOR, $documento->estado);
        $this->assertStringContainsString('<strong>válido</strong>', $documento->contenido);
        $this->assertStringNotContainsString('onclick', $documento->contenido);
        $this->assertStringNotContainsString('script', $documento->contenido);
        $this->assertDatabaseCount('series_correlativos', 0);
    }

    public function test_permite_crear_una_carta_sin_asunto_ni_destinatario(): void
    {
        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        $response = $this->actingAs($editor)->post(route('documentos.store'), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-08-15',
            'contenido' => '<p>Señores:</p><p>PRESIDENTE EJECUTIVO DE EJEMPLO S.R.L.</p><p>Presente.</p><p>Asunto: Solicitud de información.</p><p>Atentamente,</p>',
            'accion' => 'guardar',
        ]);

        $documento = Documento::query()->sole();
        $response->assertRedirect(route('documentos.show', $documento));
        $this->assertNull($documento->asunto);
        $this->assertNull($documento->destinatario);
        $this->assertSame('La Paz', $documento->lugar);
        $this->assertStringContainsString('PRESIDENTE EJECUTIVO', $documento->contenido);
    }

    public function test_permite_personalizar_el_lugar_de_emision(): void
    {
        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        $this->actingAs($editor)->post(route('documentos.store'), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-08-15',
            'lugar' => 'El Alto',
            'contenido' => '<p>Contenido libre del editor.</p>',
            'accion' => 'guardar',
        ]);

        $this->assertSame('El Alto', Documento::query()->sole()->lugar);
    }

    public function test_emite_una_carta_sin_asunto_ni_destinatario(): void
    {
        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        $this->app->instance(GeneradorArchivosDocumento::class, new class implements GeneradorArchivosDocumento
        {
            public function generar(Documento $documento): ArchivosGenerados
            {
                $pdf = 'pdf-sin-asunto-'.$documento->cite;
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
        $documento = Documento::factory()->create([
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'anio' => 2026,
            'fecha_documento' => '2026-08-15',
            'lugar' => 'La Paz',
            'asunto' => null,
            'destinatario' => null,
            'contenido' => '<p>Por medio de la presente se remite lo solicitado.</p>',
        ]);

        $this->actingAs($editor)
            ->post(route('documentos.emitir', $documento))
            ->assertRedirect();

        $documento->refresh();
        $this->assertSame('SD-ADM-NE-2026/001', $documento->cite);
        $this->assertNotNull($documento->hash_verificacion);

        $this->get(route('verificar.show', $documento->hash_verificacion))
            ->assertOk()
            ->assertSee('Documento auténtico');
    }

    public function test_la_vista_del_documento_muestra_el_encabezado_sin_textos_automaticos(): void
    {
        $editor = User::factory()->editor()->create();
        $documento = Documento::factory()->emitido()->create([
            'fecha_documento' => '2026-08-15',
            'lugar' => 'La Paz',
            'asunto' => null,
            'destinatario' => null,
            'cite' => 'SD-ADM-NE-2026/001',
        ])->load(['area', 'tipo']);

        $this->actingAs($editor)
            ->get(route('documentos.show', $documento))
            ->assertOk()
            ->assertSee('La Paz, 15 de agosto de 2026')
            ->assertSee('CITE: SD-ADM-NE-2026/001')
            ->assertDontSee('Dirigido a')
            ->assertDontSee('Señor(a):');
    }

    public function test_emision_asigna_cite_genera_archivos_y_publica_la_verificacion(): void
    {
        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        $this->app->instance(GeneradorArchivosDocumento::class, new class implements GeneradorArchivosDocumento
        {
            public function generar(Documento $documento): ArchivosGenerados
            {
                $pdf = 'pdf-oficial-'.$documento->cite;
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
        $documento = Documento::factory()->create([
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'anio' => 2026,
            'fecha_documento' => '2026-08-15',
            'contenido' => '<p>Contenido oficial</p>',
        ]);

        $this->actingAs($editor)
            ->post(route('documentos.emitir', $documento))
            ->assertRedirect();

        $documento->refresh();
        $this->assertSame('SD-ADM-NE-2026/001', $documento->cite);
        $this->assertSame(EstadoDocumento::EMITIDO, $documento->estado);
        $this->assertNotNull($documento->hash_verificacion);
        $this->assertDatabaseHas('series_correlativos', ['ultimo_correlativo' => 1]);
        $this->assertDatabaseHas('documento_eventos', ['documento_id' => $documento->id, 'evento' => 'emitido']);

        $this->get(route('verificar.show', $documento->hash_verificacion))
            ->assertOk()
            ->assertSee('Documento auténtico')
            ->assertSee($documento->cite);

        $this->actingAs($editor)
            ->put(route('documentos.update', $documento), [
                'area_id' => $area->id,
                'tipo_id' => $tipo->id,
                'fecha_documento' => '2026-08-15',
                'asunto' => 'Intento de cambio',
                'destinatario' => $documento->destinatario,
                'contenido' => '<p>No debe cambiar</p>',
                'accion' => 'guardar',
            ])
            ->assertForbidden();

        $admin = User::factory()->administrador()->create();
        $this->actingAs($admin)
            ->post(route('documentos.anular', $documento), [
                'motivo' => 'La carta fue reemplazada por una versión corregida.',
            ])
            ->assertRedirect();

        $documento->refresh();
        $this->assertSame(EstadoDocumento::ANULADO, $documento->estado);
        $this->get(route('verificar.show', $documento->hash_verificacion))
            ->assertOk()
            ->assertSee('Documento anulado');
        $this->get(route('verificar.pdf', $documento->hash_verificacion))->assertNotFound();
    }

    public function test_codigo_publico_desconocido_muestra_resultado_no_valido(): void
    {
        $this->get(route('verificar.show', str_repeat('a', 64)))
            ->assertOk()
            ->assertSee('Documento no encontrado');
    }

    public function test_alineacion_izquierda_se_guarda_correctamente(): void
    {
        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        $this->actingAs($editor)->post(route('documentos.store'), [
            'area_id'               => $area->id,
            'tipo_id'               => $tipo->id,
            'fecha_documento'       => '2026-08-21',
            'alineacion_encabezado' => 'left',
            'contenido'             => '<p>Contenido de prueba de alineación.</p>',
            'accion'                => 'guardar',
        ]);

        $this->assertSame('left', Documento::query()->sole()->alineacion_encabezado);
    }

    public function test_alineacion_centro_se_guarda_correctamente(): void
    {
        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        $this->actingAs($editor)->post(route('documentos.store'), [
            'area_id'               => $area->id,
            'tipo_id'               => $tipo->id,
            'fecha_documento'       => '2026-08-21',
            'alineacion_encabezado' => 'center',
            'contenido'             => '<p>Contenido de prueba de alineación centro.</p>',
            'accion'                => 'guardar',
        ]);

        $this->assertSame('center', Documento::query()->sole()->alineacion_encabezado);
    }

    public function test_alineacion_invalida_usa_derecha_como_default(): void
    {
        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        $this->actingAs($editor)->post(route('documentos.store'), [
            'area_id'               => $area->id,
            'tipo_id'               => $tipo->id,
            'fecha_documento'       => '2026-08-21',
            'alineacion_encabezado' => 'invalid_value',
            'contenido'             => '<p>Contenido de prueba alineación inválida.</p>',
            'accion'                => 'guardar',
        ]);

        // El request normaliza valores inválidos a 'right'
        $this->assertSame('right', Documento::query()->sole()->alineacion_encabezado);
    }

    public function test_alineacion_se_conserva_al_editar(): void
    {
        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);
        $documento = Documento::factory()->create([
            'area_id'               => $area->id,
            'tipo_id'               => $tipo->id,
            'alineacion_encabezado' => 'left',
            'contenido'             => '<p>Original.</p>',
        ]);

        $this->actingAs($editor)->put(route('documentos.update', $documento), [
            'area_id'               => $area->id,
            'tipo_id'               => $tipo->id,
            'fecha_documento'       => '2026-08-21',
            'alineacion_encabezado' => 'center',
            'contenido'             => '<p>Actualizado.</p>',
            'accion'                => 'guardar',
        ]);

        $this->assertSame('center', $documento->fresh()->alineacion_encabezado);
    }

    public function test_permite_guardar_documento_con_widgets_sdaya(): void
    {
        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        $contenidoWidgets = '<div class="sdaya-meta sdaya-align-right" data-sdaya-meta="true">'
            .'<p class="sdaya-meta-fecha" data-sdaya-widget="fecha">La Paz, 21 de agosto de 2026</p>'
            .'<p class="sdaya-meta-cite" data-sdaya-widget="cite">CITE: Pendiente</p>'
            .'</div>'
            .'<figure class="table"><table><tbody><tr><td>Detalle</td></tr></tbody></table></figure>'
            .'<div class="sdaya-qr sdaya-align-center" data-sdaya-qr="true">'
            .'<p class="sdaya-qr-placeholder">[QR_INSTITUCIONAL_SDAYA]</p>'
            .'</div>';

        $this->actingAs($editor)->post(route('documentos.store'), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-08-21',
            'contenido' => $contenidoWidgets,
            'accion' => 'guardar',
        ]);

        $doc = Documento::query()->sole();
        $this->assertStringContainsString('data-sdaya-meta="true"', $doc->contenido);
        $this->assertStringContainsString('data-sdaya-qr="true"', $doc->contenido);
        $this->assertStringContainsString('<table', $doc->contenido);
    }

    public function test_documentos_historicos_con_html_legacy_se_visualizan_correctamente(): void
    {
        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        $documentoHistorico = Documento::factory()->create([
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'cite' => 'SD-ADM-NE-2025/123',
            'estado' => EstadoDocumento::EMITIDO,
            'hash_verificacion' => 'historico-hash-1234567890abcdef',
            'contenido' => '<p><span class="ql-font-montserrat ql-size-14">Carta histórica redactada con Quill.</span></p>'
                .'<p class="ql-align-center">Contenido centrado legacy.</p>',
        ]);

        $response = $this->actingAs($editor)->get(route('documentos.show', $documentoHistorico));

        $response->assertOk();
        $response->assertSee('SD-ADM-NE-2025/123');
        $response->assertSee('ql-font-montserrat');
        $response->assertSee('ql-align-center');
    }

    public function test_caracteres_unicode_especiales_sobreviven_al_ciclo_completo_guardar_recuperar_mostrar(): void
    {
        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create([
            'codigo' => 'ADM',
            'nombre' => 'Área de Administración y Gestión de Niñez',
            'descripcion' => 'Descripción con caracteres especiales: á, é, í, ó, ú, ñ, ü, ¿, ¡, –',
        ]);
        $tipo = Tipo::factory()->create([
            'codigo' => 'NE',
            'nombre' => 'Nota Externa / Notificación Pública',
            'descripcion' => 'Tipología de comunicación formal con acentuación y símbolos.',
        ]);

        $asunto = 'Verificación pública de la Niñez y Educación: ¿Trámite ágil? ¡Confirmado!';
        $destinatario = 'Lic. Günther Peña – Director General de Coordinación';
        $lugar = 'Potosí';
        $contenido = '<p>Estimado Lic. Günther Peña:</p>'
            .'<p>Por medio de la presente, la <strong>Administración</strong> remite el <em>Registro cronológico</em>.</p>'
            .'<p>Características verificadas: <strong>á, é, í, ó, ú, ñ, ü, ¿, ¡, –, ·</strong></p>'
            .'<p>¡Muchas gracias por su atención!</p>';

        $response = $this->actingAs($editor)->post(route('documentos.store'), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-08-22',
            'lugar' => $lugar,
            'asunto' => $asunto,
            'destinatario' => $destinatario,
            'contenido' => $contenido,
            'accion' => 'guardar',
        ]);

        $documento = Documento::query()->where('asunto', $asunto)->sole();
        $response->assertRedirect(route('documentos.show', $documento));

        // 1. Verificación de almacenamiento en Base de Datos
        $this->assertSame($asunto, $documento->asunto);
        $this->assertSame($destinatario, $documento->destinatario);
        $this->assertSame($lugar, $documento->lugar);
        $this->assertStringContainsString('Lic. Günther Peña', $documento->destinatario);
        $this->assertStringContainsString('á, é, í, ó, ú, ñ, ü, ¿, ¡, –, ·', $documento->contenido);
        $this->assertStringContainsString('Administración', $documento->contenido);
        $this->assertStringContainsString('Registro cronológico', $documento->contenido);

        // 2. Verificación de renderizado en Vista Blade (recuperar -> mostrar)
        $showResponse = $this->actingAs($editor)->get(route('documentos.show', $documento));
        $showResponse->assertOk();

        // Datos dinámicos con caracteres UTF-8
        $showResponse->assertSee($asunto);
        $showResponse->assertSee($destinatario);
        $showResponse->assertSee('Potosí');
        $showResponse->assertSee('Área de Administración y Gestión de Niñez');
        $showResponse->assertSee('Nota Externa / Notificación Pública');
        $showResponse->assertSee('á, é, í, ó, ú, ñ, ü, ¿, ¡, –, ·', false);

        // Textos estáticos de la vista show.blade.php
        $showResponse->assertSee('Clasificación');
        $showResponse->assertSee('Registro cronológico');
        $showResponse->assertSee('Borrador / Vista Previa');
        $showResponse->assertSee('Volver a editar');
        $showResponse->assertSee('Finalizar carta');
        $showResponse->assertDontSee('??');
    }

    public function test_flujo_vista_previa_borrador_permite_revisar_volver_a_editar_y_finalizar_carta(): void
    {
        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create(['codigo' => 'ADM', 'nombre' => 'Gerencia']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE', 'nombre' => 'Nota Externa']);

        // 1. Crear borrador
        $response = $this->actingAs($editor)->post(route('documentos.store'), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-09-01',
            'lugar' => 'La Paz',
            'asunto' => 'Informe técnico de auditoría',
            'destinatario' => 'Lic. María Fernández',
            'contenido' => '<p>Estimada Licenciada:</p><p>Presentamos el <strong>borrador preliminar</strong>.</p>',
            'accion' => 'guardar',
        ]);

        $documento = Documento::query()->where('asunto', 'Informe técnico de auditoría')->sole();
        $response->assertRedirect(route('documentos.show', $documento));
        $this->assertTrue($documento->estaBorrador());
        $this->assertNull($documento->cite);

        // 2. Acceder a la vista previa del borrador
        $previewResponse = $this->actingAs($editor)->get(route('documentos.preview', $documento));
        $previewResponse->assertOk();
        $previewResponse->assertSee('Borrador / Vista Previa');
        $previewResponse->assertSee('Informe técnico de auditoría');
        $previewResponse->assertSee('Lic. María Fernández');
        $previewResponse->assertSee('La Paz');
        $previewResponse->assertSee('Volver a editar');
        $previewResponse->assertSee('Finalizar carta');
        $previewResponse->assertSee('CITE:');
        $previewResponse->assertSee('(Se asignará al finalizar la carta)');

        // 3. Volver a editar conservando los datos
        $editResponse = $this->actingAs($editor)->get(route('documentos.edit', $documento));
        $editResponse->assertOk();
        $editResponse->assertSee('borrador preliminar');
        $editResponse->assertSee('Guardar y ver vista previa');

        // 4. Modificar el borrador
        $updateResponse = $this->actingAs($editor)->put(route('documentos.update', $documento), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-09-01',
            'lugar' => 'La Paz',
            'asunto' => 'Informe técnico de auditoría - Revisión 2',
            'destinatario' => 'Lic. María Fernández',
            'contenido' => '<p>Estimada Licenciada:</p><p>Presentamos el <strong>borrador actualizado</strong>.</p>',
            'accion' => 'guardar',
        ]);
        $updateResponse->assertRedirect(route('documentos.show', $documento));
        $documento->refresh();
        $this->assertSame('Informe técnico de auditoría - Revisión 2', $documento->asunto);
        $this->assertTrue($documento->estaBorrador());

        // 5. Finalizar carta
        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        $emitirResponse = $this->actingAs($editor)->post(route('documentos.emitir', $documento));
        $emitirResponse->assertRedirect(route('documentos.show', $documento));

        $documento->refresh();
        $this->assertTrue($documento->estaEmitido());
        $this->assertNotNull($documento->cite);
        $this->assertSame('SD-ADM-NE-2026/001', $documento->cite);
    }

    public function test_borrador_es_privado_para_el_autor_y_admin_pero_oculto_para_otros_editores(): void
    {
        $editorA = User::factory()->editor()->create(['name' => 'Editor Autor']);
        $editorB = User::factory()->editor()->create(['name' => 'Editor Ajeno']);
        $admin = User::factory()->admin()->create();

        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        // 1. Editor A crea un borrador
        $this->actingAs($editorA)->post(route('documentos.store'), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-08-20',
            'asunto' => 'Carta Confidencial de Autor A',
            'destinatario' => 'Destinatario A',
            'contenido' => '<p>Contenido privado del borrador de A.</p>',
            'accion' => 'guardar',
        ]);

        $borradorA = Documento::query()->where('asunto', 'Carta Confidencial de Autor A')->first();
        $this->assertNotNull($borradorA);
        $this->assertSame($editorA->id, $borradorA->emitido_por);

        // 2. Editor A ve su propio borrador en index y puede acceder a él
        $respA = $this->actingAs($editorA)->get(route('documentos.index'));
        $respA->assertOk();
        $respA->assertSee('Carta Confidencial de Autor A');
        $this->actingAs($editorA)->get(route('documentos.show', $borradorA))->assertOk();
        $this->actingAs($editorA)->get(route('documentos.edit', $borradorA))->assertOk();

        // 3. Editor B NO ve el borrador de A en index y tiene acceso prohibido (403)
        $respB = $this->actingAs($editorB)->get(route('documentos.index'));
        $respB->assertOk();
        $respB->assertDontSee('Carta Confidencial de Autor A');
        $this->actingAs($editorB)->get(route('documentos.show', $borradorA))->assertForbidden();
        $this->actingAs($editorB)->get(route('documentos.edit', $borradorA))->assertForbidden();
        $this->actingAs($editorB)->post(route('documentos.emitir', $borradorA))->assertForbidden();

        // 4. El Administrador sí puede supervisar el borrador de A
        $respAdmin = $this->actingAs($admin)->get(route('documentos.index'));
        $respAdmin->assertOk();
        $respAdmin->assertSee('Carta Confidencial de Autor A');
        $this->actingAs($admin)->get(route('documentos.show', $borradorA))->assertOk();

        // 5. Cuando Editor A emite la carta, pasa a ser pública internamente
        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');
        $this->actingAs($editorA)->post(route('documentos.emitir', $borradorA));

        $borradorA->refresh();
        $this->assertTrue($borradorA->estaEmitido());

        // Ahora Editor B sí puede verla en el archivo general
        $respBEmitida = $this->actingAs($editorB)->get(route('documentos.index'));
        $respBEmitida->assertSee('Carta Confidencial de Autor A');
        $this->actingAs($editorB)->get(route('documentos.show', $borradorA))->assertOk();
    }
}
