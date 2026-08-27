<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

final class HtmlSanitizerTest extends TestCase
{
    private const PNG_UNO_POR_UNO = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private const GIF_UNO_POR_UNO = 'R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';

    private const JPEG_UNO_POR_UNO = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wgARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD3+iiigD//2Q==';

    private const WEBP_UNO_POR_UNO = 'UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==';

    public function test_elimina_elementos_y_atributos_peligrosos(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<p class="ql-align-center peligrosa" onclick="alert(1)">Hola <a href="javascript:alert(1)">enlace</a></p><iframe src="https://example.com"></iframe>',
        );

        $this->assertStringContainsString('class="ql-align-center"', $resultado);
        $this->assertStringNotContainsString('onclick', $resultado);
        $this->assertStringNotContainsString('javascript:', $resultado);
        $this->assertStringNotContainsString('iframe', $resultado);
    }

    public function test_elimina_elementos_bloqueados_con_su_contenido(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<p>antes</p><script>alert(1)</script><object data="x">oculto</object><embed src="y"></embed><style>p{}</style><p>después</p>',
        );

        $this->assertStringContainsString('<p>antes</p>', $resultado);
        $this->assertStringContainsString('<p>después</p>', $resultado);
        $this->assertStringNotContainsString('script', $resultado);
        $this->assertStringNotContainsString('object', $resultado);
        $this->assertStringNotContainsString('embed', $resultado);
        $this->assertStringNotContainsString('alert', $resultado);
        $this->assertStringNotContainsString('oculto', $resultado);
    }

    public function test_elimina_eventos_html_de_cualquier_etiqueta(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<p onload="x()" onerror="y()" onmouseover="z()">texto <img onerror="w()" src="no-valida"></p>',
        );

        $this->assertStringNotContainsString('onload', $resultado);
        $this->assertStringNotContainsString('onerror', $resultado);
        $this->assertStringNotContainsString('onmouseover', $resultado);
    }

    public function test_permite_imagenes_data_uri_de_formatos_soportados(): void
    {
        $sanitizer = new HtmlSanitizer;

        foreach ([
            'png' => self::PNG_UNO_POR_UNO,
            'gif' => self::GIF_UNO_POR_UNO,
            'jpeg' => self::JPEG_UNO_POR_UNO,
            'webp' => self::WEBP_UNO_POR_UNO,
        ] as $formato => $base64) {
            $resultado = $sanitizer->limpiar(
                '<p>Foto <img src="data:image/'.$formato.';base64,'.$base64.'" alt="Adjunto"></p>',
            );

            $this->assertStringContainsString('src="data:image/'.$formato.';base64,', $resultado, "Formato $formato debería permitirse.");
            $this->assertStringContainsString('alt="Adjunto"', $resultado);
        }
    }

    public function test_rechaza_imagenes_con_origenes_no_permitidos(): void
    {
        $sanitizer = new HtmlSanitizer;

        foreach ([
            'https://example.com/malware.png',
            'javascript:alert(1)',
            'data:text/html;base64,'.self::PNG_UNO_POR_UNO,
            'data:image/svg+xml;base64,'.base64_encode('<svg/>'),
            'data:image/png;base64,!!!!no-es-base64!!!!',
            '/storage/interna.png',
        ] as $origen) {
            $resultado = $sanitizer->limpiar('<p>x <img src="'.$origen.'"></p>');

            $this->assertStringNotContainsString('<img', $resultado, "Origen $origen debería eliminarse.");
        }
    }

    public function test_rechaza_data_uri_que_no_es_una_imagen_real(): void
    {
        $disfrazado = base64_encode('<?php echo "payload"; ?><html><body></body></html>');
        $resultado = (new HtmlSanitizer)->limpiar(
            '<p>x <img src="data:image/png;base64,'.$disfrazado.'"></p>',
        );

        $this->assertStringNotContainsString('<img', $resultado);
    }

    public function test_conserva_solo_estilos_seguros(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<span style="font-family: Arial; font-size: 12pt; color: #e60000; background-color: #ffef00; font-weight: bold; font-style: italic; text-decoration: underline; text-align: center; text-indent: 20px; position: fixed; width: 999px; background-image: url(https://rastreo.example/x.png); behavior: url(x.htc)">texto</span>',
        );

        foreach ([
            'font-family: Arial',
            'font-size: 12pt',
            'color: #e60000',
            'background-color: #ffef00',
            'font-weight: bold',
            'font-style: italic',
            'text-decoration: underline',
            'text-align: center',
            'text-indent: 20px',
        ] as $estilo) {
            $this->assertStringContainsString($estilo, $resultado);
        }

        foreach (['position', 'width', 'background-image', 'url(', 'behavior'] as $prohibido) {
            $this->assertStringNotContainsString($prohibido, $resultado);
        }
    }

    public function test_rechaza_valores_css_con_inyeccion(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<span style="font-family: Arial; expression(alert(1)); color: red; javascript:x">t</span>'
            .'<span style="color: expression(alert(2))">u</span>',
        );

        $this->assertStringNotContainsString('expression', $resultado);
        $this->assertStringNotContainsString('javascript', $resultado);
        $this->assertStringContainsString('color: red', $resultado);
        $this->assertStringContainsString('font-family: Arial', $resultado);
    }

    public function test_conserva_clases_quill_de_fuente_tamanio_alineacion_y_sangria(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<p class="ql-align-justify ql-indent-2 ql-font-montserrat ql-size-14 ql-font-desconocida ql-size-99">párrafo</p>'
            .'<p class="ql-font-times ql-size-24">otro</p>'
            .'<p class="ql-font-georgia ql-font-arial ql-font-courier ql-font-gothic">catálogo</p>',
        );

        $this->assertStringContainsString('ql-align-justify', $resultado);
        $this->assertStringContainsString('ql-indent-2', $resultado);
        $this->assertStringContainsString('ql-font-montserrat', $resultado);
        $this->assertStringContainsString('ql-size-14', $resultado);
        $this->assertStringContainsString('ql-font-times', $resultado);
        $this->assertStringContainsString('ql-size-24', $resultado);
        $this->assertStringContainsString('ql-font-georgia', $resultado);
        $this->assertStringContainsString('ql-font-arial', $resultado);
        $this->assertStringContainsString('ql-font-courier', $resultado);
        $this->assertStringContainsString('ql-font-gothic', $resultado);
        $this->assertStringNotContainsString('ql-font-desconocida', $resultado);
        $this->assertStringNotContainsString('ql-size-99', $resultado);
    }

    public function test_conserva_imagen_flotante_con_posicion_segura(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<p>Firma:</p><img class="ql-flotante" src="data:image/png;base64,'.self::PNG_UNO_POR_UNO.'" style="position: absolute; left: 55.5%; top: 40%; z-index: -1;" alt="firma">',
        );

        $this->assertStringContainsString('class="ql-flotante"', $resultado);
        $this->assertStringContainsString('position: absolute', $resultado);
        $this->assertStringContainsString('left: 55.5%', $resultado);
        $this->assertStringContainsString('top: 40%', $resultado);
        $this->assertStringContainsString('z-index: -1', $resultado);
    }

    public function test_rechaza_posicion_fuera_de_imagenes_flotantes(): void
    {
        $sanitizer = new HtmlSanitizer;

        $enSpan = $sanitizer->limpiar('<span style="position: absolute; left: 50%; top: 50%">x</span>');
        $this->assertStringNotContainsString('position', $enSpan);

        $imgSinClase = $sanitizer->limpiar(
            '<img src="data:image/png;base64,'.self::PNG_UNO_POR_UNO.'" style="position: absolute; left: 10%; top: 20%">',
        );
        $this->assertStringNotContainsString('position', $imgSinClase);

        $valoresRaros = $sanitizer->limpiar(
            '<img class="ql-flotante" src="data:image/png;base64,'.self::PNG_UNO_POR_UNO.'" style="left: calc(100% - 5px); top: url(x)">',
        );
        $this->assertStringNotContainsString('calc(', $valoresRaros);
        $this->assertStringNotContainsString('url(', $valoresRaros);
    }

    public function test_conserva_listas_y_enlaces_seguros(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<ol><li data-list="ordered" data-x="1">uno</li></ol><ul><li data-list="bullet">dos</li></ul><p><a href="https://sdaya.gob.bo">sitio</a> <a href="#ancla">ancla</a></p>',
        );

        $this->assertStringContainsString('data-list="ordered"', $resultado);
        $this->assertStringContainsString('data-list="bullet"', $resultado);
        $this->assertStringContainsString('href="https://sdaya.gob.bo"', $resultado);
        $this->assertStringContainsString('rel="noopener noreferrer"', $resultado);
        $this->assertStringContainsString('href="#ancla"', $resultado);
        $this->assertStringNotContainsString('data-x', $resultado);
    }

    public function test_conserva_separador_horizontal_hr(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<p>Párrafo 1</p><hr><p>Párrafo 2</p>',
        );

        $this->assertStringContainsString('<hr>', $resultado);
        $this->assertStringContainsString('Párrafo 1', $resultado);
        $this->assertStringContainsString('Párrafo 2', $resultado);
    }

    public function test_conserva_dimensiones_seguras_en_imagenes(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<p><img src="data:image/png;base64,'.self::PNG_UNO_POR_UNO.'" width="350" height="200" alt="Gráfico"></p>',
        );

        $this->assertStringContainsString('width="350"', $resultado);
        $this->assertStringContainsString('height="200"', $resultado);
        $this->assertStringContainsString('alt="Gráfico"', $resultado);
    }

    public function test_conserva_estructuras_de_tabla_seguras(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<table border="1"><tr style="text-align: center;"><th colspan="2">Encabezado</th></tr><tr><td>Dato 1</td><td>Dato 2</td></tr></table>',
        );

        $this->assertStringContainsString('<table', $resultado);
        $this->assertStringContainsString('border="1"', $resultado);
        $this->assertStringContainsString('text-align: center', $resultado);
        $this->assertStringContainsString('colspan="2"', $resultado);
        $this->assertStringContainsString('Dato 1', $resultado);
        $this->assertStringContainsString('Dato 2', $resultado);
    }

    public function test_conserva_etiquetas_figure_y_clases_de_ckeditor5(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<figure class="image image-style-align-center"><img src="data:image/png;base64,'.self::PNG_UNO_POR_UNO.'" alt="Foto"><figcaption>Pie de foto</figcaption></figure><figure class="table"><table><tbody><tr><td>Celda</td></tr></tbody></table></figure>',
        );

        $this->assertStringContainsString('<figure class="image image-style-align-center"', $resultado);
        $this->assertStringContainsString('<figcaption>Pie de foto</figcaption>', $resultado);
        $this->assertStringContainsString('<figure class="table"', $resultado);
        $this->assertStringContainsString('<td>Celda</td>', $resultado);
    }

    public function test_conserva_widgets_institucionales_sdaya(): void
    {
        $resultado = (new HtmlSanitizer)->limpiar(
            '<div class="sdaya-meta sdaya-align-right" data-sdaya-meta="true" data-align="right">'
            .'<p class="sdaya-meta-fecha" data-sdaya-widget="fecha">La Paz, 21 de agosto de 2026</p>'
            .'<p class="sdaya-meta-cite" data-sdaya-widget="cite">CITE: SD-ADM-NE-2026/001</p>'
            .'</div>'
            .'<div class="sdaya-qr sdaya-align-center" data-sdaya-qr="true" data-align="center">'
            .'<p class="sdaya-qr-placeholder">[QR_INSTITUCIONAL_SDAYA]</p>'
            .'</div>',
        );

        $this->assertStringContainsString('data-sdaya-meta="true"', $resultado);
        $this->assertStringContainsString('data-sdaya-qr="true"', $resultado);
        $this->assertStringContainsString('data-sdaya-widget="fecha"', $resultado);
        $this->assertStringContainsString('data-sdaya-widget="cite"', $resultado);
        $this->assertStringContainsString('class="sdaya-meta sdaya-align-right"', $resultado);
        $this->assertStringContainsString('class="sdaya-qr sdaya-align-center"', $resultado);
    }
}

