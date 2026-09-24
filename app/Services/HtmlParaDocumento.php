<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Preprocesa el HTML sanitizado del documento para su inserción en Word.
 * Convierte clases de formato en estilos inline que PHPWord interpreta,
 * fusionándolas con los estilos inline ya permitidos por el sanitizador.
 */
final class HtmlParaDocumento
{
    private const FUENTES = [
        'montserrat' => 'Montserrat',
        'arial' => 'Arial',
        'times' => 'Times New Roman',
        'courier' => 'Courier New',
        'gothic' => 'Century Gothic',
        'georgia' => 'Georgia',
    ];

    private const TAMANIOS = ['10', '11', '12', '14', '16', '18', '20', '24'];

    public function paraWord(string $html): string
    {
        $documento = new DOMDocument('1.0', 'UTF-8');
        $previo = libxml_use_internal_errors(true);

        $documento->loadHTML(
            '<?xml encoding="UTF-8"><div id="sdaya-word-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previo);

        $raiz = $documento->getElementById('sdaya-word-root');

        if (! $raiz instanceof DOMElement) {
            return $html;
        }

        // Desenvolver etiquetas <figure> de CKEditor 5 para compatibilidad total con PHPWord
        $figures = [];
        foreach ($raiz->getElementsByTagName('figure') as $fig) {
            $figures[] = $fig;
        }
        foreach ($figures as $fig) {
            $padre = $fig->parentNode;
            if (! $padre) continue;
            while ($fig->firstChild !== null) {
                $hijo = $fig->firstChild;
                if ($hijo instanceof DOMElement && $fig->hasAttribute('class')) {
                    $claseExistente = $hijo->getAttribute('class');
                    $hijo->setAttribute('class', trim($claseExistente.' '.$fig->getAttribute('class')));
                }
                $padre->insertBefore($hijo, $fig);
            }
            $padre->removeChild($fig);
        }

        // Envolver <li> huérfanos dentro de <ul> para evitar fallos de PHPWord
        $lis = [];
        foreach ($raiz->getElementsByTagName('li') as $li) {
            $lis[] = $li;
        }
        foreach ($lis as $li) {
            $padre = $li->parentNode;
            if ($padre && ! in_array(mb_strtolower($padre->nodeName), ['ul', 'ol'], true)) {
                $ul = $documento->createElement('ul');
                $padre->insertBefore($ul, $li);
                $ul->appendChild($li);
            }
        }

        // Convertir <mark> a <span> para compatibilidad con PHPWord
        $marks = [];
        foreach ($raiz->getElementsByTagName('mark') as $m) {
            $marks[] = $m;
        }
        foreach ($marks as $m) {
            $span = $documento->createElement('span');
            if ($m->hasAttribute('class')) {
                $span->setAttribute('class', $m->getAttribute('class'));
            }
            if ($m->hasAttribute('style')) {
                $span->setAttribute('style', $m->getAttribute('style'));
            }
            while ($m->firstChild !== null) {
                $span->appendChild($m->firstChild);
            }
            $m->parentNode?->replaceChild($span, $m);
        }

        foreach ($this->elementos($raiz) as $elemento) {
            $clases = $elemento->getAttribute('class');

            if ($clases === '') {
                continue;
            }

            $derivados = $this->estilosDesdeClases($clases);

            if ($derivados === []) {
                $elemento->removeAttribute('class');

                continue;
            }

            $elemento->setAttribute('style', $this->fusionarEstilos($elemento->getAttribute('style'), $derivados));
            $elemento->removeAttribute('class');
        }

        $resultado = '';

        foreach ($raiz->childNodes as $nodo) {
            $resultado .= $documento->saveHTML($nodo);
        }

        return $this->serializarXmlSeguro(trim($resultado));
    }

    /**
     * PHPWord analiza el HTML con loadXML (estricto). Se decodifican las
     * entidades con nombre no XML y se auto-cierran los elementos vacíos
     * para que el documento sea XML válido.
     */
    private function serializarXmlSeguro(string $html): string
    {
        $html = (string) preg_replace_callback(
            '/&([a-zA-Z][a-zA-Z0-9]{1,30});/',
            static function (array $coincidencia): string {
                if (in_array($coincidencia[1], ['amp', 'lt', 'gt', 'quot', 'apos'], true)) {
                    return $coincidencia[0];
                }

                $texto = html_entity_decode($coincidencia[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return $texto === $coincidencia[0] ? '&#38;'.$coincidencia[1].';' : $texto;
            },
            $html,
        );

        return (string) preg_replace(
            '/<(img|br|hr|embed|input|source|col|area|base|link|meta|param|track|wbr)((?:[^>"\']|"[^"]*"|\'[^\']*\')*?)\s*\/?>/i',
            '<$1$2 />',
            $html,
        );
    }

    /** @return list<DOMElement> */
    private function elementos(DOMNode $padre): array
    {
        $lista = [];

        foreach ($padre->childNodes as $hijo) {
            if (! $hijo instanceof DOMElement) {
                continue;
            }

            $lista[] = $hijo;

            foreach ($this->elementos($hijo) as $descendiente) {
                $lista[] = $descendiente;
            }
        }

        return $lista;
    }

    /** @return array<string, string> */
    private function estilosDesdeClases(string $clases): array
    {
        $estilos = [];

        foreach (preg_split('/\s+/', trim($clases)) ?: [] as $clase) {
            foreach ($this->estilosDeClase($clase) as $propiedad => $valor) {
                $estilos[$propiedad] = $valor;
            }
        }

        return $estilos;
    }

    /** @return array<string, string> */
    private function estilosDeClase(string $clase): array
    {
        if ($clase === 'ql-align-center' || $clase === 'text-align-center' || $clase === 'sdaya-align-center' || $clase === 'image-style-align-center') {
            return ['text-align' => 'center'];
        }

        if ($clase === 'ql-align-right' || $clase === 'text-align-right' || $clase === 'sdaya-align-right' || $clase === 'image-style-align-right') {
            return ['text-align' => 'right'];
        }

        if ($clase === 'ql-align-justify' || $clase === 'text-align-justify') {
            return ['text-align' => 'justify'];
        }

        if ($clase === 'text-align-left' || $clase === 'sdaya-align-left' || $clase === 'image-style-align-left') {
            return ['text-align' => 'left'];
        }

        if ($clase === 'ql-direction-rtl') {
            return ['direction' => 'rtl', 'text-align' => 'right'];
        }

        if ($clase === 'text-tiny') {
            return ['font-size' => '8pt'];
        }

        if ($clase === 'text-small') {
            return ['font-size' => '10pt'];
        }

        if ($clase === 'text-big') {
            return ['font-size' => '14pt'];
        }

        if ($clase === 'text-huge') {
            return ['font-size' => '18pt'];
        }

        if (preg_match('/^ql-indent-([1-8])$/', $clase, $nivel) === 1) {
            return ['margin-left' => ((int) $nivel[1] * 20).'px'];
        }

        if (preg_match('/^ql-font-([a-z]+)$/', $clase, $fuente) === 1
            && isset(self::FUENTES[$fuente[1]])) {
            return ['font-family' => self::FUENTES[$fuente[1]]];
        }

        if (preg_match('/^ql-size-(\d{2})$/', $clase, $tamanio) === 1
            && in_array($tamanio[1], self::TAMANIOS, true)) {
            return ['font-size' => $tamanio[1].'pt'];
        }

        if ($clase === 'marker-yellow') {
            return ['background-color' => '#fef08a'];
        }

        if ($clase === 'marker-green') {
            return ['background-color' => '#bbf7d0'];
        }

        if ($clase === 'marker-pink') {
            return ['background-color' => '#fbcfe8'];
        }

        if ($clase === 'marker-blue') {
            return ['background-color' => '#bae6fd'];
        }

        if ($clase === 'pen-red') {
            return ['color' => '#dc2626'];
        }

        if ($clase === 'pen-green') {
            return ['color' => '#16a34a'];
        }

        return [];
    }

    /** Los estilos inline existentes tienen prioridad sobre los derivados de clases. */
    private function fusionarEstilos(string $existentes, array $derivados): string
    {
        $finales = [];

        foreach (explode(';', $existentes) ?: [] as $declaracion) {
            $partes = explode(':', $declaracion, 2);

            if (count($partes) === 2 && trim($partes[0]) !== '' && trim($partes[1]) !== '') {
                $finales[trim(mb_strtolower($partes[0]))] = trim($partes[1]);
            }
        }

        foreach ($derivados as $propiedad => $valor) {
            $finales[$propiedad] ??= $valor;
        }

        return implode('; ', array_map(
            static fn (string $propiedad, string $valor): string => "{$propiedad}: {$valor}",
            array_keys($finales),
            array_values($finales),
        ));
    }
}
