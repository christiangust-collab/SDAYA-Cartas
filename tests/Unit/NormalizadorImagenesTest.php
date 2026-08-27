<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\NormalizadorImagenes;
use PHPUnit\Framework\TestCase;

final class NormalizadorImagenesTest extends TestCase
{
    private const PNG_UNO_POR_UNO = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private const GIF_UNO_POR_UNO = 'R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';

    private const WEBP_UNO_POR_UNO = 'UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==';

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('imagick') && ! extension_loaded('gd')) {
            $this->markTestSkipped('La normalización de imágenes requiere Imagick o GD.');
        }
    }

    public function test_convierte_webp_y_gif_a_png(): void
    {
        $servicio = new NormalizadorImagenes;

        $webp = $servicio->normalizar('<p>x <img src="data:image/webp;base64,'.self::WEBP_UNO_POR_UNO.'"></p>');
        $gif = $servicio->normalizar('<p>x <img src="data:image/gif;base64,'.self::GIF_UNO_POR_UNO.'"></p>');

        $this->assertStringContainsString('data:image/png;base64,', $webp);
        $this->assertStringContainsString('data:image/png;base64,', $gif);
        $this->assertStringContainsString('width="1"', $webp);
        $this->assertStringContainsString('width="1"', $gif);
    }

    public function test_conserva_png_y_agrega_dimensiones(): void
    {
        $resultado = (new NormalizadorImagenes)->normalizar(
            '<p>x <img src="data:image/png;base64,'.self::PNG_UNO_POR_UNO.'" alt="captura"></p>',
        );

        $this->assertStringContainsString('data:image/png;base64,', $resultado);
        $this->assertStringContainsString('alt="captura"', $resultado);
        $this->assertStringContainsString('width="1"', $resultado);
    }

    public function test_elimina_imagenes_invalidas(): void
    {
        $resultado = (new NormalizadorImagenes)->normalizar(
            '<p>x <img src="data:image/png;base64,INVALIDO"> <img src="https://externa.example/a.png"></p>',
        );

        $this->assertStringNotContainsString('<img', $resultado);
    }

    public function test_devuelve_el_html_intacto_si_no_hay_imagenes(): void
    {
        $html = '<p>Texto <strong>simple</strong></p>';

        $this->assertSame($html, (new NormalizadorImagenes)->normalizar($html));
    }
}
