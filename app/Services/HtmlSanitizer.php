<?php

declare(strict_types=1);

namespace App\Services;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Sanitizador de HTML enriquecido del editor.
 *
 * Bloquea por completo script, iframe, object, embed, svg, formularios y todo
 * atributo/evento no permitido. Solo sobreviven las etiquetas, clases, estilos
 * e imágenes en línea expresamente autorizados.
 */
final class HtmlSanitizer
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'p', 'div', 'figure', 'figcaption', 'br', 'hr', 'strong', 'b', 'em', 'i', 'u', 's',
        'ol', 'ul', 'li', 'blockquote', 'h1', 'h2', 'h3',
        'span', 'a', 'img',
        'table', 'thead', 'tbody', 'tr', 'td', 'th',
    ];

    /** @var list<string> */
    private const BLOCKED_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
        'form', 'input', 'button', 'textarea', 'select', 'option',
    ];

    /** MIME types aceptados para imágenes incrustadas en línea. */
    private const IMAGEN_MIMES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    /**
     * Propiedades CSS permitidas con su patrón de valor seguro.
     * Cualquier otra propiedad se descarta.
     *
     * @var array<string, non-empty-string>
     */
    private const ESTILOS_PERMITIDOS = [
        'font-family' => '/^[A-Za-z0-9 ,.\'"-]{1,120}$/',
        'font-size' => '/^\d{1,3}(\.\d{1,2})?(pt|px|em|rem)$/',
        'color' => '/^(?:#[0-9a-fA-F]{3,8}|[a-zA-Z]{3,25}|rgb\([0-9 ,.%]{1,40}\)|rgba\([0-9 ,.%]{1,60}\))$/',
        'background-color' => '/^(?:#[0-9a-fA-F]{3,8}|[a-zA-Z]{3,25}|rgb\([0-9 ,.%]{1,40}\)|rgba\([0-9 ,.%]{1,60}\))$/',
        'font-weight' => '/^(normal|bold|\d{3})$/',
        'font-style' => '/^(normal|italic|oblique)$/',
        'text-decoration' => '/^(none|underline|line-through|overline)(\s+(none|underline|line-through|overline)){0,2}$/',
        'text-align' => '/^(left|right|center|justify)$/',
        'text-indent' => '/^-?\d{1,4}(\.\d{1,2})?(pt|px|em)?$/',
        'margin-left' => '/^-?\d{1,4}(\.\d{1,2})?(pt|px|em|%|rem)?$/',
        'margin-right' => '/^-?\d{1,4}(\.\d{1,2})?(pt|px|em|%|rem)?$/',
        'margin-top' => '/^-?\d{1,4}(\.\d{1,2})?(pt|px|em|%|rem)?$/',
        'margin-bottom' => '/^-?\d{1,4}(\.\d{1,2})?(pt|px|em|%|rem)?$/',
        'margin' => '/^-?\d{1,3}(\.\d{1,2})?(pt|px|em|rem|%|auto)(\s+-?\d{1,3}(\.\d{1,2})?(pt|px|em|rem|%|auto)){0,3}$/',
        'width' => '/^\d{1,4}(\.\d{1,2})?(pt|px|em|%|rem)$/',
        'height' => '/^\d{1,4}(\.\d{1,2})?(pt|px|em|%|rem)$/',
        'min-width' => '/^\d{1,4}(\.\d{1,2})?(pt|px|em|%|rem)$/',
        'max-width' => '/^\d{1,4}(\.\d{1,2})?(pt|px|em|%|rem)$/',
        'border' => '/^(none|\d{1,2}px\s+(solid|dashed|dotted)\s+(?:#[0-9a-fA-F]{3,8}|[a-zA-Z]{3,25}|rgb\([0-9 ,.%]{1,40}\)|rgba\([0-9 ,.%]{1,60}\)))$/',
        'border-top' => '/^(none|\d{1,2}px\s+(solid|dashed|dotted)\s+(?:#[0-9a-fA-F]{3,8}|[a-zA-Z]{3,25}|rgb\([0-9 ,.%]{1,40}\)|rgba\([0-9 ,.%]{1,60}\)))$/',
        'border-bottom' => '/^(none|\d{1,2}px\s+(solid|dashed|dotted)\s+(?:#[0-9a-fA-F]{3,8}|[a-zA-Z]{3,25}|rgb\([0-9 ,.%]{1,40}\)|rgba\([0-9 ,.%]{1,60}\)))$/',
        'border-left' => '/^(none|\d{1,2}px\s+(solid|dashed|dotted)\s+(?:#[0-9a-fA-F]{3,8}|[a-zA-Z]{3,25}|rgb\([0-9 ,.%]{1,40}\)|rgba\([0-9 ,.%]{1,60}\)))$/',
        'border-right' => '/^(none|\d{1,2}px\s+(solid|dashed|dotted)\s+(?:#[0-9a-fA-F]{3,8}|[a-zA-Z]{3,25}|rgb\([0-9 ,.%]{1,40}\)|rgba\([0-9 ,.%]{1,60}\)))$/',
        'border-collapse' => '/^(collapse|separate)$/',
        'border-spacing' => '/^\d{1,3}(\.\d{1,2})?(pt|px|em)$/',
        'padding' => '/^\d{1,3}(\.\d{1,2})?(pt|px|em|rem)(\s+\d{1,3}(\.\d{1,2})?(pt|px|em|rem)){0,3}$/',
        'padding-top' => '/^\d{1,3}(\.\d{1,2})?(pt|px|em|rem)$/',
        'padding-bottom' => '/^\d{1,3}(\.\d{1,2})?(pt|px|em|rem)$/',
        'padding-left' => '/^\d{1,3}(\.\d{1,2})?(pt|px|em|rem)$/',
        'padding-right' => '/^\d{1,3}(\.\d{1,2})?(pt|px|em|rem)$/',
        'vertical-align' => '/^(top|middle|bottom|baseline)$/',
        'line-height' => '/^(\d{1,2}(\.\d{1,2})?|\d{1,3}%|\d{1,3}px|\d{1,3}pt)$/',
        'page-break-after' => '/^(always|auto|avoid|left|right)$/',
        'page-break-before' => '/^(always|auto|avoid|left|right)$/',
        'page-break-inside' => '/^(auto|avoid)$/',
        'break-after' => '/^(page|auto|avoid)$/',
        'display' => '/^(none|block|inline|inline-block|table|table-row|table-cell|flex)$/',
    ];

    /** Fragmentos prohibidos dentro de cualquier valor CSS aceptado. */
    private const CSS_PROHIBIDO = '/url\s*\(|expression\s*\(|javascript|@import|@charset|behavior|binding|\\\\/i';

    /**
     * Propiedades de posicionamiento permitidas ÚNICAMENTE en imágenes
     * flotantes (clase ql-flotante). Solo porcentajes numéricos: sin url(),
     * sin calc(), sin otras unidades.
     *
     * @var array<string, non-empty-string>
     */
    private const ESTILOS_FLOTANTE = [
        'position' => '/^absolute$/',
        'left' => '/^-?\d{1,3}(\.\d{1,2})?%$/',
        'top' => '/^-?\d{1,3}(\.\d{1,2})?%$/',
        'z-index' => '/^-?\d{1,2}$/',
    ];

    public function limpiar(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="sdaya-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('sdaya-root');

        if (! $root instanceof DOMElement) {
            return '';
        }

        $this->limpiarHijos($root);

        $resultado = '';

        foreach ($root->childNodes as $node) {
            $resultado .= $document->saveHTML($node);
        }

        return trim($resultado);
    }

    private function limpiarHijos(DOMNode $padre): void
    {
        $hijos = [];

        foreach ($padre->childNodes as $hijo) {
            $hijos[] = $hijo;
        }

        foreach ($hijos as $hijo) {
            if ($hijo instanceof DOMComment) {
                $padre->removeChild($hijo);

                continue;
            }

            if (! $hijo instanceof DOMElement) {
                continue;
            }

            $etiqueta = mb_strtolower($hijo->tagName);

            if (in_array($etiqueta, self::BLOCKED_WITH_CONTENT, true)) {
                $padre->removeChild($hijo);

                continue;
            }

            $this->limpiarHijos($hijo);

            if (! in_array($etiqueta, self::ALLOWED_TAGS, true)) {
                while ($hijo->firstChild !== null) {
                    $padre->insertBefore($hijo->firstChild, $hijo);
                }

                $padre->removeChild($hijo);

                continue;
            }

            $this->limpiarAtributos($hijo);

            if ($etiqueta === 'img' && ! $hijo->hasAttribute('src')) {
                $padre->removeChild($hijo);
            }
        }
    }

    private function limpiarAtributos(DOMElement $elemento): void
    {
        $atributos = [];

        foreach ($elemento->attributes as $atributo) {
            $atributos[$atributo->name] = $atributo->value;
        }

        foreach (array_keys($atributos) as $nombre) {
            $elemento->removeAttribute($nombre);
        }

        $clases = $this->clasesPermitidas($atributos['class'] ?? '');

        if ($clases !== []) {
            $elemento->setAttribute('class', implode(' ', $clases));
        }

        $esFlotante = $elemento->tagName === 'img' && in_array('ql-flotante', $clases, true);
        $estilos = $this->estilosPermitidos($atributos['style'] ?? '', $esFlotante, $elemento->tagName);

        if ($estilos !== '') {
            $elemento->setAttribute('style', $estilos);
        }

        if ($elemento->tagName === 'img') {
            $origen = trim($atributos['src'] ?? '');

            if ($this->esImagenSegura($origen)) {
                $elemento->setAttribute('src', $origen);
            }

            $descripcion = mb_substr(trim(strip_tags((string) ($atributos['alt'] ?? ''))), 0, 200);

            if ($descripcion !== '') {
                $elemento->setAttribute('alt', $descripcion);
            }

            if (isset($atributos['width']) && preg_match('/^\d{1,4}$/', trim((string) $atributos['width'])) === 1) {
                $elemento->setAttribute('width', trim((string) $atributos['width']));
            }

            if (isset($atributos['height']) && preg_match('/^\d{1,4}$/', trim((string) $atributos['height'])) === 1) {
                $elemento->setAttribute('height', trim((string) $atributos['height']));
            }

            return;
        }

        if (in_array($elemento->tagName, ['table', 'td', 'th'], true)) {
            if (isset($atributos['border']) && preg_match('/^\d{1,2}$/', trim((string) $atributos['border'])) === 1) {
                $elemento->setAttribute('border', trim((string) $atributos['border']));
            }

            if (in_array($elemento->tagName, ['td', 'th'], true)) {
                if (isset($atributos['colspan']) && preg_match('/^\d{1,3}$/', trim((string) $atributos['colspan'])) === 1) {
                    $elemento->setAttribute('colspan', trim((string) $atributos['colspan']));
                }
                if (isset($atributos['rowspan']) && preg_match('/^\d{1,3}$/', trim((string) $atributos['rowspan'])) === 1) {
                    $elemento->setAttribute('rowspan', trim((string) $atributos['rowspan']));
                }
            }
        }

        if (in_array($elemento->tagName, ['div', 'p', 'span', 'figure', 'section'], true)) {
            foreach (['data-sdaya-meta', 'data-sdaya-qr', 'data-sdaya-widget', 'data-align', 'data-page-break', 'data-sdaya-page-break'] as $dataAttr) {
                if (isset($atributos[$dataAttr])) {
                    $val = preg_replace('/[^\w-]/', '', (string) $atributos[$dataAttr]);
                    if ($val !== '') {
                        $elemento->setAttribute($dataAttr, $val);
                    }
                }
            }
        }

        if ($elemento->tagName === 'a') {
            $href = trim($atributos['href'] ?? '');

            if (preg_match('/^(https?:\/\/|mailto:|#)/i', $href) === 1) {
                $elemento->setAttribute('href', $href);

                if (str_starts_with(mb_strtolower($href), 'http')) {
                    $elemento->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }

        if ($elemento->tagName === 'li') {
            $tipoLista = $atributos['data-list'] ?? '';

            if (in_array($tipoLista, ['ordered', 'bullet', 'checked', 'unchecked'], true)) {
                $elemento->setAttribute('data-list', $tipoLista);
            }
        }
    }

    /**
     * Acepta únicamente data URI de imágenes PNG/JPEG/GIF/WebP válidas.
     * El contenido se decodifica y se verifica como imagen real para
     * descartar cargas disfrazadas.
     */
    private function esImagenSegura(string $origen): bool
    {
        if (preg_match('/^data:image\/(?:png|jpe?g|gif|webp);base64,([A-Za-z0-9+\/=\s]+)$/i', $origen, $coincidencia) !== 1) {
            return false;
        }

        $binario = base64_decode(preg_replace('/\s+/', '', $coincidencia[1]) ?? '', true);

        if ($binario === false || $binario === '') {
            return false;
        }

        set_error_handler(static fn (): bool => true);

        try {
            $info = getimagesizefromstring($binario);
        } finally {
            restore_error_handler();
        }

        return $info !== false && in_array((string) $info['mime'], self::IMAGEN_MIMES, true);
    }

    /**
     * Reconstruye el atributo style conservando solo propiedades y valores
     * seguros. Las imágenes flotantes admiten además las propiedades de
     * posicionamiento (position/left/top) con valores numéricos en porcentaje.
     */
    private function estilosPermitidos(string $estilo, bool $flotante = false, string $tagName = ''): string
    {
        $limpios = [];
        $esElementoTexto = in_array(mb_strtolower($tagName), ['span', 'strong', 'em', 'b', 'i', 'u', 's', 'sub', 'sup', 'small', 'a'], true);

        foreach (explode(';', $estilo) as $declaracion) {
            $partes = explode(':', $declaracion, 2);

            if (count($partes) !== 2) {
                continue;
            }

            $propiedad = mb_strtolower(trim($partes[0]));
            $valor = trim($partes[1]);

            if ($valor === '' || preg_match(self::CSS_PROHIBIDO, $valor) === 1) {
                continue;
            }

            if ($esElementoTexto && in_array($propiedad, ['width', 'height', 'min-width', 'max-width'], true)) {
                continue;
            }

            $patron = self::ESTILOS_PERMITIDOS[$propiedad]
                ?? ($flotante ? self::ESTILOS_FLOTANTE[$propiedad] ?? null : null);

            if ($patron === null || preg_match($patron, $valor) !== 1) {
                continue;
            }

            $limpios[] = $propiedad.': '.$valor;
        }

        return implode('; ', $limpios);
    }

    /** @return list<string> */
    private function clasesPermitidas(string $clases): array
    {
        return array_values(array_filter(
            preg_split('/\s+/', trim($clases)) ?: [],
            static fn (string $clase): bool => preg_match(
                '/^(ql-align-(center|right|justify)|ql-indent-[1-8]|ql-direction-rtl'
                .'|ql-font-(montserrat|arial|times|courier|gothic|georgia)'
                .'|ql-size-(10|11|12|14|16|18|20|24)'
                .'|ql-flotante'
                .'|sdaya-(meta|qr|align-left|align-center|align-right|widget|meta-fecha|meta-cite|meta-line|meta-date|qr-image|qr-label|qr-placeholder|chip|cite-chip|page-break)'
                .'|image|image-style-(align-left|align-center|align-right|block|inline|side|wrap-left|wrap-right|break-text)'
                .'|image_resized|table|table-bordered|table-striped|ck-table-resized|ck-widget|page-break|ck-page-break|text-(tiny|small|big|huge)|text-align-(left|center|right|justify))$/',
                $clase,
            ) === 1,
        ));
    }
}
