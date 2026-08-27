<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\HtmlParaDocumento;
use PHPUnit\Framework\TestCase;

final class HtmlParaDocumentoTest extends TestCase
{
    private HtmlParaDocumento $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new HtmlParaDocumento;
    }

    public function test_convierte_clases_quill_de_alineacion_y_sangria(): void
    {
        $resultado = $this->servicio->paraWord(
            '<p class="ql-align-center">centro</p><p class="ql-align-right">derecha</p><p class="ql-align-justify">justificado</p><p class="ql-indent-3">sangría</p>',
        );

        $this->assertStringContainsString('style="text-align: center"', $resultado);
        $this->assertStringContainsString('style="text-align: right"', $resultado);
        $this->assertStringContainsString('style="text-align: justify"', $resultado);
        $this->assertStringContainsString('margin-left: 60px', $resultado);
        $this->assertStringNotContainsString('class="ql-', $resultado);
    }

    public function test_convierte_fuentes_y_tamanios_quill(): void
    {
        $resultado = $this->servicio->paraWord(
            '<span class="ql-font-montserrat">a</span><span class="ql-font-times ql-size-18">b</span>'
            .'<span class="ql-font-courier ql-size-10">c</span><span class="ql-font-georgia ql-size-16">d</span>'
            .'<span class="ql-font-gothic ql-size-24">e</span>',
        );

        $this->assertStringContainsString('font-family: Montserrat', $resultado);
        $this->assertStringContainsString('font-family: Times New Roman', $resultado);
        $this->assertStringContainsString('font-size: 18pt', $resultado);
        $this->assertStringContainsString('font-family: Courier New', $resultado);
        $this->assertStringContainsString('font-size: 10pt', $resultado);
        $this->assertStringContainsString('font-family: Georgia', $resultado);
        $this->assertStringContainsString('font-size: 16pt', $resultado);
        $this->assertStringContainsString('font-family: Century Gothic', $resultado);
        $this->assertStringContainsString('font-size: 24pt', $resultado);
    }

    public function test_fusiona_con_estilos_inline_existentes_dandoles_prioridad(): void
    {
        $resultado = $this->servicio->paraWord(
            '<span class="ql-font-arial" style="color: #e60000; font-size: 14pt">rojo</span>',
        );

        $this->assertStringContainsString('font-family: Arial', $resultado);
        $this->assertStringContainsString('color: #e60000', $resultado);
        $this->assertStringContainsString('font-size: 14pt', $resultado);
    }

    public function test_desenvuelve_figures_y_convierte_clases_ckeditor5(): void
    {
        $resultado = $this->servicio->paraWord(
            '<figure class="table"><table><tbody><tr><td class="text-align-center">Contenido</td></tr></tbody></table></figure>'
            .'<p class="text-align-right">Párrafo derecho</p>'
            .'<p class="text-big">Texto grande</p>',
        );

        $this->assertStringNotContainsString('<figure', $resultado);
        $this->assertStringContainsString('<table', $resultado);
        $this->assertStringContainsString('style="text-align: center"', $resultado);
        $this->assertStringContainsString('style="text-align: right"', $resultado);
        $this->assertStringContainsString('style="font-size: 14pt"', $resultado);
    }
}
