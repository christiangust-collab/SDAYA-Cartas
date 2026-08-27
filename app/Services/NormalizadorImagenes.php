<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use Imagick;
use RuntimeException;

/**
 * Normaliza las imágenes en línea del documento para su inclusión estable
 * en PDF (DomPDF) y Word (PHPWord): convierte WebP/GIF a PNG o JPEG,
 * escala proporcionalmente para que la imagen no desborde la página y fija
 * los atributos width/height.
 */
final class NormalizadorImagenes
{
    private const ANCHO_MAXIMO = 600;

    private const ALTO_MAXIMO = 800;

    public function normalizar(string $html): string
    {
        if (! str_contains($html, '<img')) {
            return $html;
        }

        $documento = new DOMDocument('1.0', 'UTF-8');
        $previo = libxml_use_internal_errors(true);

        $documento->loadHTML(
            '<?xml encoding="UTF-8"><div id="sdaya-imagenes-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previo);

        $raiz = $documento->getElementById('sdaya-imagenes-root');

        if (! $raiz instanceof DOMElement) {
            return $html;
        }

        $imagenes = [];

        foreach ($raiz->getElementsByTagName('img') as $imagen) {
            $imagenes[] = $imagen;
        }

        foreach ($imagenes as $imagen) {
            if (! $this->procesar($imagen)) {
                $imagen->parentNode?->removeChild($imagen);
            }
        }

        $resultado = '';

        foreach ($raiz->childNodes as $nodo) {
            $resultado .= $documento->saveHTML($nodo);
        }

        return trim($resultado);
    }

    private function procesar(DOMElement $imagen): bool
    {
        $origen = trim($imagen->getAttribute('src'));

        if (preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,(.*)$/is', $origen, $coincidencia) !== 1) {
            return false;
        }

        $binario = base64_decode(preg_replace('/\s+/', '', $coincidencia[2]) ?? '', true);

        if ($binario === false || $binario === '') {
            return false;
        }

        set_error_handler(static fn (): bool => true);

        try {
            $info = getimagesizefromstring($binario);
        } finally {
            restore_error_handler();
        }

        if ($info === false || (int) $info[0] < 1 || (int) $info[1] < 1) {
            return false;
        }

        [$anchoOriginal, $altoOriginal] = [(int) $info[0], (int) $info[1]];
        $mime = strtolower((string) $info['mime']);
        $formatoSalida = in_array($mime, ['image/png', 'image/jpeg'], true)
            ? ($mime === 'image/png' ? 'png' : 'jpeg')
            : 'png';

        [$ancho, $alto] = $this->dimensionesEscaladas($anchoOriginal, $altoOriginal);
        $requiereConversion = $mime !== 'image/'.$formatoSalida;
        $requiereEscala = [$ancho, $alto] !== [$anchoOriginal, $altoOriginal];

        $datos = $binario;

        if ($requiereConversion || $requiereEscala) {
            $procesado = $this->reprocesar($binario, $formatoSalida, $requiereEscala ? [$ancho, $alto] : null);

            if ($procesado === null) {
                return false;
            }

            [$datos] = $procesado;
        }

        $imagen->setAttribute('src', 'data:image/'.$formatoSalida.';base64,'.base64_encode($datos));
        $imagen->setAttribute('width', (string) $ancho);
        $imagen->setAttribute('height', (string) $alto);

        return true;
    }

    /** @return array{0: int, 1: int} */
    private function dimensionesEscaladas(int $ancho, int $alto): array
    {
        $factor = min(1, self::ANCHO_MAXIMO / $ancho, self::ALTO_MAXIMO / $alto);

        return [
            max(1, (int) round($ancho * $factor)),
            max(1, (int) round($alto * $factor)),
        ];
    }

    /**
     * Convierte y reescala usando Imagick si está disponible y GD como
     * respaldo. Devuelve null si ninguna vía puede procesar la imagen.
     *
     * @param  array{0: int, 1: int}|null  $escala
     * @return array{0: string, 1: string}|null
     */
    private function reprocesar(string $binario, string $formato, ?array $escala): ?array
    {
        $viaImagick = extension_loaded('imagick')
            ? $this->reprocesarConImagick($binario, $formato, $escala)
            : null;

        return $viaImagick ?? $this->reprocesarConGd($binario, $formato, $escala);
    }

    /**
     * @param  array{0: int, 1: int}|null  $escala
     * @return array{0: string, 1: string}|null
     */
    private function reprocesarConImagick(string $binario, string $formato, ?array $escala): ?array
    {
        try {
            $imagick = new Imagick;
            $imagick->readImageBlob($binario);

            if ($escala !== null) {
                $imagick->resizeImage($escala[0], $escala[1], Imagick::FILTER_LANCZOS, 1, true);
            }

            $imagick->setImageFormat($formato);
            $salida = $imagick->getImagesBlob();
            $imagick->clear();
            $imagick->destroy();

            return [$salida, $formato];
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * @param  array{0: int, 1: int}|null  $escala
     * @return array{0: string, 1: string}|null
     */
    private function reprocesarConGd(string $binario, string $formato, ?array $escala): ?array
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $imagen = @imagecreatefromstring($binario);

        if ($imagen === false) {
            return null;
        }

        imagealphablending($imagen, true);
        imagesavealpha($imagen, true);

        if ($escala !== null) {
            $escalada = imagecreatetruecolor($escala[0], $escala[1]);
            imagealphablending($escalada, false);
            imagesavealpha($escalada, true);
            imagecopyresampled($escalada, $imagen, 0, 0, 0, 0, $escala[0], $escala[1], imagesx($imagen), imagesy($imagen));
            imagedestroy($imagen);
            $imagen = $escalada;
        }

        ob_start();

        $ok = $formato === 'jpeg'
            ? imagejpeg($imagen, null, 90)
            : imagepng($imagen, null, 8);

        $datos = (string) ob_get_clean();
        imagedestroy($imagen);

        return $ok ? [$datos, $formato] : null;
    }
}
