<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Image as PhpWordImage;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\Table as PhpWordTable;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Importador de alta fidelidad para documentos DOCX de Microsoft Word.
 *
 * Analiza directamente el paquete OpenXML (WordprocessingML) para conservar
 * fielmente la estructura visual y semántica del documento original:
 *  - Párrafos y saltos de línea reales.
 *  - Alineaciones exactas: izquierda, centrada, derecha y justificada.
 *  - Formato de texto: negrita, cursiva, subrayado, tachado, colores y resaltados.
 *  - Tipografías y tamaños en puntos.
 *  - Sangrías y márgenes de párrafo.
 *  - Listas numeradas y con viñetas agrupadas coherentemente.
 *  - Tablas completas con celdas, bordes, fondos y combinaciones de columnas.
 *  - Imágenes incrustadas con dimensiones reales en base64.
 *  - Enlaces e hipervínculos.
 *
 * El HTML resultante es 100% compatible con CKEditor 5 y pasa por HtmlSanitizer.
 */
final class DocxImportadorService
{
    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const R_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const A_NS = 'http://schemas.openxmlformats.org/drawingml/2006/main';
    private const PIC_NS = 'http://schemas.openxmlformats.org/drawingml/2006/picture';
    private const WP_NS = 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing';
    private const V_NS = 'urn:schemas-microsoft-com:vml';

    private const HIGHLIGHT_COLORS = [
        'yellow'      => '#FFFF00',
        'green'       => '#00FF00',
        'cyan'        => '#00FFFF',
        'magenta'     => '#FF00FF',
        'blue'        => '#0000FF',
        'red'         => '#FF0000',
        'darkblue'    => '#000080',
        'darkcyan'    => '#008080',
        'darkgreen'   => '#008000',
        'darkmagenta' => '#800080',
        'darkred'     => '#800000',
        'darkyellow'  => '#808000',
        'darkgray'    => '#808080',
        'lightgray'   => '#D3D3D3',
        'black'       => '#000000',
    ];

    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    /**
     * Importa un archivo DOCX y devuelve HTML compatible con CKEditor 5.
     *
     * @throws RuntimeException si el archivo no existe o no es un DOCX válido.
     */
    public function importar(string $rutaArchivo, bool $omitirFirma = false): string
    {
        $res = $this->importarConMetadatos($rutaArchivo, $omitirFirma);

        return $res['html'];
    }

    /**
     * Importa un archivo DOCX y devuelve tanto el HTML procesado como los metadatos detectados
     * (destinatario, asunto/referencia, fecha, lugar, firmante).
     *
     * @return array{
     *     html: string,
     *     html_completo: string,
     *     destinatario: ?string,
     *     asunto: ?string,
     *     lugar: ?string,
     *     fecha: ?string,
     *     firmante_detectado: ?string,
     *     cargo_detectado: ?string,
     *     tiene_firma_detectada: bool
     * }
     */
    public function importarConMetadatos(string $rutaArchivo, bool $omitirFirma = true): array
    {
        if (! file_exists($rutaArchivo)) {
            throw new RuntimeException('El archivo DOCX especificado no existe.');
        }

        try {
            return $this->importarViaOpenXmlConMetadatos($rutaArchivo, $omitirFirma);
        } catch (Throwable) {
            // Respaldo mediante PHPWord si el ZIP no cuenta con la estructura estándar
            $html = $this->importarViaPhpWord($rutaArchivo);
            $limpio = $this->sanitizer->limpiar($this->postProcesarHtml($html));

            return [
                'html' => $limpio,
                'html_completo' => $limpio,
                'destinatario' => null,
                'asunto' => null,
                'lugar' => null,
                'fecha' => null,
                'firmante_detectado' => null,
                'cargo_detectado' => null,
                'tiene_firma_detectada' => false,
            ];
        }
    }

    /**
     * Motor principal de extracción directa OpenXML para máxima fidelidad.
     *
     * @return array{
     *     html: string,
     *     html_completo: string,
     *     destinatario: ?string,
     *     asunto: ?string,
     *     lugar: ?string,
     *     fecha: ?string,
     *     firmante_detectado: ?string,
     *     cargo_detectado: ?string,
     *     tiene_firma_detectada: bool
     * }
     */
    private function importarViaOpenXmlConMetadatos(string $rutaArchivo, bool $omitirFirma): array
    {
        $zip = new ZipArchive();
        if ($zip->open($rutaArchivo) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo como paquete ZIP.');
        }

        try {
            $relaciones = $this->extraerRelaciones($zip);
            $numbering = $this->extraerNumbering($zip);
            $xmlDocumento = $zip->getFromName('word/document.xml');

            if ($xmlDocumento === false) {
                throw new RuntimeException('No se encontró word/document.xml en el archivo DOCX.');
            }

            $dom = new DOMDocument();
            $prev = libxml_use_internal_errors(true);
            $dom->loadXML($xmlDocumento, LIBXML_NONET | LIBXML_COMPACT);
            libxml_clear_errors();
            libxml_use_internal_errors($prev);

            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', self::W_NS);
            $xpath->registerNamespace('r', self::R_NS);
            $xpath->registerNamespace('a', self::A_NS);
            $xpath->registerNamespace('pic', self::PIC_NS);
            $xpath->registerNamespace('wp', self::WP_NS);
            $xpath->registerNamespace('v', self::V_NS);

            $body = $xpath->query('//w:body')->item(0);
            if (! $body instanceof DOMElement) {
                return [
                    'html' => '',
                    'html_completo' => '',
                    'destinatario' => null,
                    'asunto' => null,
                    'lugar' => null,
                    'fecha' => null,
                    'firmante_detectado' => null,
                    'cargo_detectado' => null,
                    'tiene_firma_detectada' => false,
                ];
            }

            $elementos = [];
            foreach ($body->childNodes as $nodo) {
                if ($nodo instanceof DOMElement && $nodo->localName !== 'sectPr') {
                    $elementos[] = $nodo;
                }
            }

            $metas = $this->detectarMetadatos($elementos);
            $infoFirma = $this->detectarFirma($elementos, $xpath);

            // Generar HTML completo
            $htmlCompletoCrudo = $this->procesarListaNodos($elementos, $xpath, $relaciones, $numbering, $zip);
            $htmlCompleto = $this->sanitizer->limpiar($this->postProcesarHtml($htmlCompletoCrudo));

            // Generar HTML sin firma si se solicita
            if ($omitirFirma && $infoFirma['indice_inicio'] !== null) {
                $elementosSinFirma = array_slice($elementos, 0, $infoFirma['indice_inicio']);
                $htmlLimpioCrudo = $this->procesarListaNodos($elementosSinFirma, $xpath, $relaciones, $numbering, $zip);
                $htmlLimpio = $this->sanitizer->limpiar($this->postProcesarHtml($htmlLimpioCrudo));
            } else {
                $htmlLimpio = $htmlCompleto;
            }

            return [
                'html' => $htmlLimpio,
                'html_completo' => $htmlCompleto,
                'destinatario' => $metas['destinatario'],
                'asunto' => $metas['asunto'],
                'lugar' => $metas['lugar'],
                'fecha' => $metas['fecha'],
                'firmante_detectado' => $infoFirma['firmante'],
                'cargo_detectado' => $infoFirma['cargo'],
                'tiene_firma_detectada' => $infoFirma['indice_inicio'] !== null,
            ];
        } finally {
            $zip->close();
        }
    }

    /**
     * Detecta metadatos institucionales en la cabecera del documento (destinatario, referencia, fecha, lugar).
     *
     * @param list<DOMElement> $elementos
     * @return array{destinatario: ?string, asunto: ?string, lugar: ?string, fecha: ?string}
     */
    private function detectarMetadatos(array $elementos): array
    {
        $destinatario = null;
        $asunto = null;
        $lugar = null;
        $fecha = null;

        $max = min(12, count($elementos));
        for ($i = 0; $i < $max; $i++) {
            $txt = trim($elementos[$i]->textContent);
            if ($txt === '') {
                continue;
            }

            // Lugar y fecha: ej. "La Paz, 15 de julio de 2026"
            if ($fecha === null && preg_match('/^(?:([A-Za-zÁ-ú\s]+),\s*)?(\d{1,2})\s+de\s+([a-zA-Zá-úÁ-Ú]+)\s+de\s+(\d{4})/u', $txt, $m)) {
                $lugar = ! empty($m[1]) ? trim($m[1]) : null;
                $meses = [
                    'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04',
                    'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08',
                    'septiembre' => '09', 'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12',
                ];
                $mesNom = mb_strtolower(trim($m[3]));
                $mesNum = $meses[$mesNom] ?? '01';
                $dia = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $fecha = "{$m[4]}-{$mesNum}-{$dia}";
            }

            // Destinatario: "Señores:" -> siguiente párrafo o resto del texto
            if ($destinatario === null && preg_match('/^(?:señor(?:es|a)?|sres\.?|a\s*:|para\s*:)\s*(.*)$/ui', $txt, $m)) {
                $rest = trim($m[1]);
                if ($rest !== '' && mb_strlen($rest) > 3) {
                    $destinatario = $rest;
                } elseif (isset($elementos[$i + 1])) {
                    $nextTxt = trim($elementos[$i + 1]->textContent);
                    if ($nextTxt !== '' && ! preg_match('/^(?:presente|ref|cite)/i', $nextTxt)) {
                        $destinatario = $nextTxt;
                    }
                }
            }

            // Referencia / Asunto: "REF.: PROPUESTA..."
            if ($asunto === null && preg_match('/^(?:ref(?:\.|erencia)?|asunto|objeto)\s*[:.-]\s*(.+)$/ui', $txt, $m)) {
                $asunto = trim($m[1]);
            }
        }

        return compact('destinatario', 'asunto', 'lugar', 'fecha');
    }

    /**
     * Detecta el bloque de pie de firma al final del documento.
     *
     * @param list<DOMElement> $elementos
     * @return array{indice_inicio: ?int, firmante: ?string, cargo: ?string}
     */
    private function detectarFirma(array $elementos, DOMXPath $xpath): array
    {
        $indiceFirma = null;
        $firmante = null;
        $cargo = null;

        $total = count($elementos);
        for ($i = $total - 1; $i >= max(0, $total - 8); $i--) {
            $elem = $elementos[$i];
            $txt = trim($elem->textContent);

            $esFirma = false;
            if ($elem->localName === 'tbl') {
                if (preg_match('/(?:gerente|director|coordinador|administrador|presidente|representante|m[oó]vil|email|celular)/ui', $txt)) {
                    $esFirma = true;
                }
            } elseif ($elem->localName === 'p') {
                if (preg_match('/(?:c\.?c\.?\s*archivo|gerente\s+general|director\s+ejecutivo)/ui', $txt)) {
                    $esFirma = true;
                }
            }

            if ($esFirma) {
                $indiceFirma = $i;
                if (preg_match('/(?:MSc\.?|Lic\.?|Ing\.?|Dr\.?|Sr\.?)\s*([A-Za-zÁ-ú\s]+?)(?:GERENTE|DIRECTOR|\n|\r|$)/u', $txt, $m)) {
                    $firmante = trim($m[1]);
                }
                if (preg_match('/(GERENTE\s+GENERAL|DIRECTOR\s+EJECUTIVO|COORDINADOR\s+[A-ZÁ-Ú\s]+)/u', $txt, $m)) {
                    $cargo = trim($m[1]);
                }
            }
        }

        // Si se detectó firma, retroceder para incluir imágenes de rúbrica y párrafos vacíos de separación
        if ($indiceFirma !== null) {
            while ($indiceFirma > 0) {
                $prev = $elementos[$indiceFirma - 1];
                $prevTxt = trim($prev->textContent);
                $hasDrawing = $xpath->query('.//w:drawing', $prev)->length > 0;

                if ($prevTxt === '' || $hasDrawing) {
                    $indiceFirma--;
                } else {
                    break;
                }
            }
        }

        return ['indice_inicio' => $indiceFirma, 'firmante' => $firmante, 'cargo' => $cargo];
    }

    /**
     * Procesa una lista de elementos conservando la agrupación de listas y tablas.
     *
     * @param list<DOMElement> $elementos
     * @param array<string, string> $relaciones
     * @param array<string, string> $numbering
     */
    private function procesarListaNodos(
        array $elementos,
        DOMXPath $xpath,
        array $relaciones,
        array $numbering,
        ZipArchive $zip,
    ): string {
        $html = '';
        $listaActiva = null;

        foreach ($elementos as $nodo) {
            if ($nodo->localName === 'p') {
                $infoLista = $this->obtenerInfoLista($nodo, $xpath, $numbering);

                if ($infoLista !== null) {
                    $tipoLista = $infoLista['tipo'];
                    if ($listaActiva === null || $listaActiva['tipo'] !== $tipoLista) {
                        if ($listaActiva !== null) {
                            $html .= '</'.$listaActiva['tipo'].'>';
                        }
                        $listaActiva = ['tipo' => $tipoLista, 'nivel' => $infoLista['nivel']];
                        $html .= '<'.$tipoLista.'>';
                    }

                    $html .= $this->procesarItemLista($nodo, $xpath, $relaciones, $zip, $infoLista['nivel'], $tipoLista);

                    continue;
                }

                if ($listaActiva !== null) {
                    $html .= '</'.$listaActiva['tipo'].'>';
                    $listaActiva = null;
                }

                $html .= $this->procesarParrafo($nodo, $xpath, $relaciones, $zip);

                continue;
            }

            if ($nodo->localName === 'tbl') {
                if ($listaActiva !== null) {
                    $html .= '</'.$listaActiva['tipo'].'>';
                    $listaActiva = null;
                }

                $html .= $this->procesarTabla($nodo, $xpath, $relaciones, $numbering, $zip);

                continue;
            }
        }

        if ($listaActiva !== null) {
            $html .= '</'.$listaActiva['tipo'].'>';
        }

        return $html;
    }

    /**
     * Procesa un elemento <w:p> completo con sus estilos de párrafo y sus hijos.
     *
     * @param array<string, string> $relaciones
     */
    private function procesarParrafo(DOMElement $p, DOMXPath $xpath, array $relaciones, ZipArchive $zip): string
    {
        // Si el párrafo contiene únicamente un dibujo/imagen, emitirlo directamente como bloque
        $drawings = $xpath->query('w:r/w:drawing | w:r/w:pict', $p);
        $texto = trim($p->textContent);
        if ($drawings->length === 1 && $texto === '') {
            $imgHtml = $this->procesarDrawing($drawings->item(0), $xpath, $relaciones, $zip, true);
            if ($imgHtml !== '') {
                return $imgHtml;
            }
        }

        // Si contiene saltos de página (soft o hard), dividir el párrafo coherentemente
        $hasPageBreak = $xpath->query('.//w:lastRenderedPageBreak | .//w:br[@w:type="page"]', $p)->length > 0;
        if ($hasPageBreak) {
            return $this->procesarParrafoConSalto($p, $xpath, $relaciones, $zip);
        }

        $contenido = $this->procesarContenidoParrafo($p, $xpath, $relaciones, $zip);
        $estilos = $this->extraerEstilosParrafo($p, $xpath);

        $tag = 'p';
        $pStyle = $xpath->query('w:pPr/w:pStyle/@w:val', $p)->item(0)?->nodeValue ?? '';
        if (preg_match('/^(?:heading|titulo)\s*1$/i', $pStyle) === 1) {
            $tag = 'h1';
        } elseif (preg_match('/^(?:heading|titulo)\s*2$/i', $pStyle) === 1) {
            $tag = 'h2';
        } elseif (preg_match('/^(?:heading|titulo)\s*3$/i', $pStyle) === 1) {
            $tag = 'h3';
        }

        if ($contenido === '') {
            $attrEstilo = $estilos !== [] ? ' style="'.$this->formatearEstilos($estilos).'"' : '';

            return '<'.$tag.$attrEstilo.'><br></'.$tag.'>';
        }

        $attrEstilo = $estilos !== [] ? ' style="'.$this->formatearEstilos($estilos).'"' : '';

        return '<'.$tag.$attrEstilo.'>'.$contenido.'</'.$tag.'>';
    }

    /**
     * Procesa un párrafo que contiene un salto de página, emitiendo el indicador de página visual.
     *
     * @param array<string, string> $relaciones
     */
    private function procesarParrafoConSalto(DOMElement $p, DOMXPath $xpath, array $relaciones, ZipArchive $zip): string
    {
        $saltoHtml = '<div class="page-break" style="page-break-after:always;"><span style="display:none;">&nbsp;</span></div>';
        $estilos = $this->extraerEstilosParrafo($p, $xpath);
        $attrEstilo = $estilos !== [] ? ' style="'.$this->formatearEstilos($estilos).'"' : '';

        $segmentoAntes = '';
        $segmentoDespues = '';
        $encontrado = false;

        foreach ($p->childNodes as $nodo) {
            if (! $nodo instanceof DOMElement) {
                continue;
            }

            $tieneSalto = $xpath->query('.//w:lastRenderedPageBreak | .//w:br[@w:type="page"]', $nodo)->length > 0;

            if ($tieneSalto) {
                $encontrado = true;
                if ($nodo->localName === 'r') {
                    $segmentoDespues .= $this->procesarRun($nodo, $xpath, $relaciones, $zip);
                }
            } else {
                $fragmento = '';
                if ($nodo->localName === 'r') {
                    $fragmento = $this->procesarRun($nodo, $xpath, $relaciones, $zip);
                } elseif ($nodo->localName === 'hyperlink') {
                    $fragmento = $this->procesarHyperlink($nodo, $xpath, $relaciones, $zip);
                } elseif ($nodo->localName === 'drawing' || $nodo->localName === 'pict') {
                    $fragmento = $this->procesarDrawing($nodo, $xpath, $relaciones, $zip, false);
                }

                if (! $encontrado) {
                    $segmentoAntes .= $fragmento;
                } else {
                    $segmentoDespues .= $fragmento;
                }
            }
        }

        $out = '';
        if (trim($segmentoAntes) !== '') {
            $out .= '<p'.$attrEstilo.'>'.$segmentoAntes.'</p>';
        }
        $out .= $saltoHtml;
        if (trim($segmentoDespues) !== '') {
            $out .= '<p'.$attrEstilo.'>'.$segmentoDespues.'</p>';
        }

        return $out;
    }

    /**
     * Procesa un elemento de lista <li> manejando saltos de página internos.
     *
     * @param array<string, string> $relaciones
     */
    private function procesarItemLista(DOMElement $p, DOMXPath $xpath, array $relaciones, ZipArchive $zip, int $nivel, string $tipoLista = 'ul'): string
    {
        $hasPageBreak = $xpath->query('.//w:lastRenderedPageBreak | .//w:br[@w:type="page"]', $p)->length > 0;
        $saltoHtml = '<div class="page-break" style="page-break-after:always;"><span style="display:none;">&nbsp;</span></div>';

        if ($hasPageBreak) {
            $segmentoAntes = '';
            $segmentoDespues = '';
            $encontrado = false;

            foreach ($p->childNodes as $nodo) {
                if (! $nodo instanceof DOMElement) {
                    continue;
                }

                $tieneSalto = $xpath->query('.//w:lastRenderedPageBreak | .//w:br[@w:type="page"]', $nodo)->length > 0;
                if ($tieneSalto) {
                    $encontrado = true;
                    if ($nodo->localName === 'r') {
                        $segmentoDespues .= $this->procesarRun($nodo, $xpath, $relaciones, $zip);
                    }
                } else {
                    $fragmento = $nodo->localName === 'r' ? $this->procesarRun($nodo, $xpath, $relaciones, $zip) : '';
                    if (! $encontrado) {
                        $segmentoAntes .= $fragmento;
                    } else {
                        $segmentoDespues .= $fragmento;
                    }
                }
            }

            $sangriaStyle = $nivel > 0 ? ' style="margin-left: '.($nivel * 20).'px;"' : '';

            $parteAntes = trim($segmentoAntes) !== '' ? '<li'.$sangriaStyle.'>'.$segmentoAntes.'</li>' : '';
            $parteDespues = trim($segmentoDespues) !== '' ? '<li'.$sangriaStyle.'>'.$segmentoDespues.'</li>' : '';

            return $parteAntes.'</'.$tipoLista.'>'.$saltoHtml.'<'.$tipoLista.'>'.$parteDespues;
        }

        $contenidoItem = $this->procesarContenidoParrafo($p, $xpath, $relaciones, $zip);
        $sangriaStyle = $nivel > 0 ? ' style="margin-left: '.($nivel * 20).'px;"' : '';

        return '<li'.$sangriaStyle.'>'.($contenidoItem !== '' ? $contenidoItem : '&nbsp;').'</li>';
    }

    /**
     * Procesa los runs (<w:r>), hyperlinks (<w:hyperlink>) y drawings hijos de un párrafo.
     *
     * @param array<string, string> $relaciones
     */
    private function procesarContenidoParrafo(DOMElement $p, DOMXPath $xpath, array $relaciones, ZipArchive $zip): string
    {
        $html = '';

        foreach ($p->childNodes as $nodo) {
            if (! $nodo instanceof DOMElement) {
                continue;
            }

            if ($nodo->localName === 'r') {
                $html .= $this->procesarRun($nodo, $xpath, $relaciones, $zip);

                continue;
            }

            if ($nodo->localName === 'hyperlink') {
                $html .= $this->procesarHyperlink($nodo, $xpath, $relaciones, $zip);

                continue;
            }

            if ($nodo->localName === 'drawing' || $nodo->localName === 'pict') {
                $html .= $this->procesarDrawing($nodo, $xpath, $relaciones, $zip, false);

                continue;
            }
        }

        return $html;
    }

    /**
     * Procesa un enlace hipervínculo.
     *
     * @param array<string, string> $relaciones
     */
    private function procesarHyperlink(DOMElement $nodo, DOMXPath $xpath, array $relaciones, ZipArchive $zip): string
    {
        $rId = $nodo->getAttributeNS(self::R_NS, 'id') ?: $nodo->getAttribute('r:id');
        $url = $relaciones[$rId] ?? '';
        $textoEnlace = '';

        foreach ($nodo->childNodes as $hijoLink) {
            if ($hijoLink instanceof DOMElement && $hijoLink->localName === 'r') {
                $textoEnlace .= $this->procesarRun($hijoLink, $xpath, $relaciones, $zip);
            }
        }

        if ($url !== '' && $textoEnlace !== '') {
            return '<a href="'.htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8').'" target="_blank" rel="noopener noreferrer">'.$textoEnlace.'</a>';
        }

        return $textoEnlace;
    }

    /**
     * Procesa un elemento <w:r> con sus formatos tipográficos y contenido textual.
     *
     * @param array<string, string> $relaciones
     */
    private function procesarRun(DOMElement $r, DOMXPath $xpath, array $relaciones, ZipArchive $zip): string
    {
        $contenido = '';

        foreach ($r->childNodes as $hijo) {
            if (! $hijo instanceof DOMElement) {
                continue;
            }

            if ($hijo->localName === 't') {
                $contenido .= htmlspecialchars($hijo->nodeValue ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } elseif ($hijo->localName === 'br' || $hijo->localName === 'cr') {
                if ($hijo->getAttributeNS(self::W_NS, 'type') !== 'page' && $hijo->getAttribute('w:type') !== 'page') {
                    $contenido .= '<br>';
                }
            } elseif ($hijo->localName === 'tab') {
                $contenido .= ' &emsp; ';
            } elseif ($hijo->localName === 'drawing' || $hijo->localName === 'pict') {
                $contenido .= $this->procesarDrawing($hijo, $xpath, $relaciones, $zip, false);
            }
        }

        if ($contenido === '') {
            return '';
        }

        $bold = $xpath->query('w:rPr/w:b', $r)->length > 0 && $xpath->query('w:rPr/w:b/@w:val', $r)->item(0)?->nodeValue !== '0';
        $italic = $xpath->query('w:rPr/w:i', $r)->length > 0 && $xpath->query('w:rPr/w:i/@w:val', $r)->item(0)?->nodeValue !== '0';
        $underlineVal = $xpath->query('w:rPr/w:u/@w:val', $r)->item(0)?->nodeValue;
        $underline = $underlineVal !== null && $underlineVal !== 'none';
        $strike = $xpath->query('w:rPr/w:strike', $r)->length > 0 || $xpath->query('w:rPr/w:dstrike', $r)->length > 0;

        $estilosInline = [];

        $fuente = $xpath->query('w:rPr/w:rFonts/@w:ascii', $r)->item(0)?->nodeValue
            ?? $xpath->query('w:rPr/w:rFonts/@w:hAnsi', $r)->item(0)?->nodeValue;
        if ($fuente !== null && trim($fuente) !== '') {
            $estilosInline['font-family'] = trim($fuente).', sans-serif';
        }

        // Tamaño (en half-points en Word, dividir por 2 para pt)
        $szVal = $xpath->query('w:rPr/w:sz/@w:val', $r)->item(0)?->nodeValue;
        if ($szVal !== null && is_numeric($szVal) && (int) $szVal > 0) {
            $pts = round((int) $szVal / 2, 1);
            $estilosInline['font-size'] = $pts.'pt';
        }

        $colorVal = $xpath->query('w:rPr/w:color/@w:val', $r)->item(0)?->nodeValue;
        if ($colorVal !== null && preg_match('/^[0-9A-Fa-f]{6}$/', $colorVal) === 1 && strtolower($colorVal) !== 'auto') {
            $estilosInline['color'] = '#'.$colorVal;
        }

        $highlightVal = strtolower((string) ($xpath->query('w:rPr/w:highlight/@w:val', $r)->item(0)?->nodeValue ?? ''));
        if (isset(self::HIGHLIGHT_COLORS[$highlightVal])) {
            $estilosInline['background-color'] = self::HIGHLIGHT_COLORS[$highlightVal];
        }

        $shdVal = $xpath->query('w:rPr/w:shd/@w:fill', $r)->item(0)?->nodeValue;
        if ($shdVal !== null && preg_match('/^[0-9A-Fa-f]{6}$/', $shdVal) === 1 && strtolower($shdVal) !== 'auto') {
            $estilosInline['background-color'] = '#'.$shdVal;
        }

        if ($bold) {
            $contenido = '<strong>'.$contenido.'</strong>';
        }
        if ($italic) {
            $contenido = '<em>'.$contenido.'</em>';
        }
        if ($underline) {
            $contenido = '<u>'.$contenido.'</u>';
        }
        if ($strike) {
            $contenido = '<s>'.$contenido.'</s>';
        }

        if ($estilosInline !== []) {
            $contenido = '<span style="'.$this->formatearEstilos($estilosInline).'">'.$contenido.'</span>';
        }

        return $contenido;
    }

    /**
     * Procesa una tabla <w:tbl> completa conservando filas, columnas, anchos y estilos de celda.
     *
     * @param array<string, string> $relaciones
     * @param array<string, string> $numbering
     */
    private function procesarTabla(
        DOMElement $tbl,
        DOMXPath $xpath,
        array $relaciones,
        array $numbering,
        ZipArchive $zip,
    ): string {
        $tblBordersVal = $xpath->query('w:tblPr/w:tblBorders/w:top/@w:val', $tbl)->item(0)?->nodeValue;
        $esSinBorde = ($tblBordersVal === 'none' || $tblBordersVal === 'nil');

        $html = '<figure class="table"><table style="width: 100%; border-collapse: collapse;"><tbody>';

        foreach ($tbl->childNodes as $fila) {
            if (! $fila instanceof DOMElement || $fila->localName !== 'tr') {
                continue;
            }

            // Suma de dxa en la fila para calcular anchos porcentuales proporcionales
            $totalDxaFila = 0;
            foreach ($fila->childNodes as $c) {
                if ($c instanceof DOMElement && $c->localName === 'tc') {
                    $w = (int) ($xpath->query('w:tcPr/w:tcW/@w:w', $c)->item(0)?->nodeValue ?? 0);
                    $totalDxaFila += $w;
                }
            }

            $html .= '<tr>';

            foreach ($fila->childNodes as $celda) {
                if (! $celda instanceof DOMElement || $celda->localName !== 'tc') {
                    continue;
                }

                $attrs = [];
                $estilosCelda = [
                    'border' => $esSinBorde ? 'none' : '1px solid #cbd5e1',
                    'padding' => '6px 10px',
                    'vertical-align' => 'top',
                ];

                // Width porcentual si está definido en dxa
                $celdaDxa = (int) ($xpath->query('w:tcPr/w:tcW/@w:w', $celda)->item(0)?->nodeValue ?? 0);
                if ($celdaDxa > 0 && $totalDxaFila > 0) {
                    $pct = round(($celdaDxa / $totalDxaFila) * 100, 1);
                    $estilosCelda['width'] = $pct.'%';
                }

                // Column span (gridSpan)
                $gridSpan = $xpath->query('w:tcPr/w:gridSpan/@w:val', $celda)->item(0)?->nodeValue;
                if ($gridSpan !== null && is_numeric($gridSpan) && (int) $gridSpan > 1) {
                    $attrs[] = 'colspan="'.(int) $gridSpan.'"';
                }

                // Fondo de celda (shd)
                $shd = $xpath->query('w:tcPr/w:shd/@w:fill', $celda)->item(0)?->nodeValue;
                if ($shd !== null && preg_match('/^[0-9A-Fa-f]{6}$/', $shd) === 1 && strtolower($shd) !== 'auto') {
                    $estilosCelda['background-color'] = '#'.$shd;
                }

                $vAlign = $xpath->query('w:tcPr/w:vAlign/@w:val', $celda)->item(0)?->nodeValue;
                if ($vAlign === 'center') {
                    $estilosCelda['vertical-align'] = 'middle';
                } elseif ($vAlign === 'bottom') {
                    $estilosCelda['vertical-align'] = 'bottom';
                }

                $attrs[] = 'style="'.$this->formatearEstilos($estilosCelda).'"';

                $hijosCelda = [];
                foreach ($celda->childNodes as $h) {
                    if ($h instanceof DOMElement) {
                        $hijosCelda[] = $h;
                    }
                }
                $contenidoCelda = $this->procesarListaNodos($hijosCelda, $xpath, $relaciones, $numbering, $zip);

                $html .= '<td '.implode(' ', $attrs).'>'.($contenidoCelda !== '' ? $contenidoCelda : '&nbsp;').'</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody></table></figure>';

        return $html;
    }

    /**
     * Extrae imágenes incrustadas desde elementos <w:drawing> o <w:pict> y las convierte a Data URI.
     *
     * @param array<string, string> $relaciones
     */
    private function procesarDrawing(
        DOMElement $drawing,
        DOMXPath $xpath,
        array $relaciones,
        ZipArchive $zip,
        bool $esBloque = false,
    ): string {
        $rId = '';

        // Buscar nodo a:blip (OpenXML moderno)
        $blip = $xpath->query('.//a:blip', $drawing)->item(0);
        if ($blip instanceof DOMElement) {
            $rId = $blip->getAttributeNS(self::R_NS, 'embed')
                ?: $blip->getAttribute('r:embed')
                ?: $blip->getAttributeNS(self::R_NS, 'link')
                ?: $blip->getAttribute('r:link');
        }

        // Si no es a:blip, buscar v:imagedata (VML legacy)
        if ($rId === '') {
            $vImg = $xpath->query('.//v:imagedata', $drawing)->item(0);
            if ($vImg instanceof DOMElement) {
                $rId = $vImg->getAttributeNS(self::R_NS, 'id')
                    ?: $vImg->getAttribute('r:id')
                    ?: $vImg->getAttributeNS(self::R_NS, 'href')
                    ?: $vImg->getAttribute('r:href');
            }
        }

        if ($rId === '' || ! isset($relaciones[$rId])) {
            return '';
        }

        $target = $relaciones[$rId];
        $target = str_replace(['../', '..\\'], '', $target);
        $target = ltrim($target, '/\\');
        if (! str_starts_with($target, 'word/')) {
            $target = 'word/'.$target;
        }

        $binario = $zip->getFromName($target);
        if ($binario === false || $binario === '') {
            // Intentar buscar archivo en media/ directamente
            $soloNombre = basename($target);
            $binario = $zip->getFromName('word/media/'.$soloNombre);
        }

        if ($binario === false || $binario === '') {
            return '';
        }

        $info = @getimagesizefromstring($binario);
        $mime = $info ? (string) $info['mime'] : 'image/png';

        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
            return '';
        }

        // Obtener dimensiones en EMUs (1 inch = 914400 EMUs = 96 px)
        $extent = $xpath->query('.//wp:extent', $drawing)->item(0);
        $dimAttr = '';
        if ($extent instanceof DOMElement) {
            $cx = (int) $extent->getAttribute('cx');
            $cy = (int) $extent->getAttribute('cy');
            if ($cx > 0 && $cy > 0) {
                $anchoPx = max(1, (int) round(($cx / 914400) * 96));
                $altoPx = max(1, (int) round(($cy / 914400) * 96));
                $dimAttr = ' width="'.$anchoPx.'" height="'.$altoPx.'"';
            }
        }

        $dataUri = 'data:'.$mime.';base64,'.base64_encode($binario);

        if ($esBloque) {
            return '<figure class="image"><img src="'.$dataUri.'" alt="Imagen importada"'.$dimAttr.'></figure>';
        }

        return '<img src="'.$dataUri.'" alt="Imagen importada"'.$dimAttr.'>';
    }

    /**
     * Extrae las propiedades de alineación, sangría y margen del párrafo.
     *
     * @return array<string, string>
     */
    private function extraerEstilosParrafo(DOMElement $p, DOMXPath $xpath): array
    {
        $estilos = [];

        // Alineación (w:jc)
        $jc = strtolower((string) ($xpath->query('w:pPr/w:jc/@w:val', $p)->item(0)?->nodeValue ?? ''));
        if ($jc === 'right' || $jc === 'end') {
            $estilos['text-align'] = 'right';
        } elseif ($jc === 'center') {
            $estilos['text-align'] = 'center';
        } elseif ($jc === 'both' || $jc === 'distribute') {
            $estilos['text-align'] = 'justify';
        } elseif ($jc === 'left' || $jc === 'start') {
            $estilos['text-align'] = 'left';
        }

        // Sangría izquierda (w:ind/@w:left en twips: 1 pt = 20 twips)
        // Se aplica únicamente si no está alineado a la derecha para no empujar el texto fuera
        if ($jc !== 'right' && $jc !== 'end') {
            $indLeft = $xpath->query('w:pPr/w:ind/@w:left', $p)->item(0)?->nodeValue;
            if ($indLeft !== null && is_numeric($indLeft) && (int) $indLeft > 0) {
                $pts = round((int) $indLeft / 20, 1);
                $estilos['margin-left'] = $pts.'pt';
            }
        }

        return $estilos;
    }

    /**
     * Determina si un párrafo pertenece a una lista numerada o con viñetas.
     *
     * @param array<string, string> $numbering
     * @return array{tipo: 'ol'|'ul', nivel: int}|null
     */
    private function obtenerInfoLista(DOMElement $p, DOMXPath $xpath, array $numbering): ?array
    {
        $numId = $xpath->query('w:pPr/w:numPr/w:numId/@w:val', $p)->item(0)?->nodeValue;
        if ($numId === null || $numId === '0') {
            return null;
        }

        $ilvl = (int) ($xpath->query('w:pPr/w:numPr/w:ilvl/@w:val', $p)->item(0)?->nodeValue ?? 0);
        $tipo = $numbering[$numId] ?? 'ul';

        return [
            'tipo' => $tipo === 'ol' ? 'ol' : 'ul',
            'nivel' => max(0, $ilvl),
        ];
    }

    /**
     * Extrae el mapa de relaciones de word/_rels/document.xml.rels.
     *
     * @return array<string, string>
     */
    private function extraerRelaciones(ZipArchive $zip): array
    {
        $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
        if ($relsXml === false) {
            return [];
        }

        $dom = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadXML($relsXml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $relaciones = [];
        foreach ($dom->getElementsByTagName('Relationship') as $rel) {
            if ($rel instanceof DOMElement) {
                $id = $rel->getAttribute('Id');
                $target = $rel->getAttribute('Target');
                if ($id !== '' && $target !== '') {
                    $relaciones[$id] = $target;
                }
            }
        }

        return $relaciones;
    }

    /**
     * Extrae la configuración de listas de word/numbering.xml para distinguir
     * listas numeradas (decimal, etc.) de viñetas (bullet).
     *
     * @return array<string, string> Map de numId => 'ol'|'ul'
     */
    private function extraerNumbering(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('word/numbering.xml');
        if ($xml === false) {
            return [];
        }

        $dom = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);

        // Mapear abstractNumId => formato ('ol' o 'ul')
        $abstractFormats = [];
        foreach ($xpath->query('//w:abstractNum') as $abstractNum) {
            if ($abstractNum instanceof DOMElement) {
                $absId = $abstractNum->getAttributeNS(self::W_NS, 'abstractNumId') ?: $abstractNum->getAttribute('w:abstractNumId');
                $numFmt = $xpath->query('.//w:numFmt/@w:val', $abstractNum)->item(0)?->nodeValue;
                $abstractFormats[$absId] = ($numFmt === 'bullet') ? 'ul' : 'ol';
            }
        }

        // Mapear numId => formato
        $numbering = [];
        foreach ($xpath->query('//w:num') as $num) {
            if ($num instanceof DOMElement) {
                $numId = $num->getAttributeNS(self::W_NS, 'numId') ?: $num->getAttribute('w:numId');
                $absRef = $xpath->query('w:abstractNumId/@w:val', $num)->item(0)?->nodeValue;
                if ($numId !== '' && $absRef !== null) {
                    $numbering[$numId] = $abstractFormats[$absRef] ?? 'ul';
                }
            }
        }

        return $numbering;
    }

    /**
     * Respaldo utilizando la librería PHPWord si la lectura OpenXML directa falla.
     */
    private function importarViaPhpWord(string $rutaArchivo): string
    {
        try {
            $phpWord = IOFactory::load($rutaArchivo, 'Word2007');
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo leer el archivo DOCX: '.$e->getMessage(), 0, $e);
        }

        $html = '';
        foreach ($phpWord->getSections() as $seccion) {
            $html .= $this->convertirContenedorPhpWord($seccion);
        }

        return $html;
    }

    private function convertirContenedorPhpWord(AbstractContainer $contenedor): string
    {
        $html = '';
        foreach ($contenedor->getElements() as $elemento) {
            if ($elemento instanceof ListItem) {
                $texto = htmlspecialchars($elemento->getTextObject()->getText(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $html .= '<ul><li>'.$texto.'</li></ul>';
            } elseif ($elemento instanceof PhpWordTable) {
                $html .= '<figure class="table"><table style="width:100%;border-collapse:collapse;"><tbody>';
                foreach ($elemento->getRows() as $fila) {
                    $html .= '<tr>';
                    foreach ($fila->getCells() as $celda) {
                        $html .= '<td style="border:1px solid #cbd5e1;padding:6px 10px;">'.$this->convertirContenedorPhpWord($celda).'</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table></figure>';
            } elseif ($elemento instanceof TextRun) {
                $contenido = '';
                foreach ($elemento->getElements() as $hijo) {
                    if ($hijo instanceof Text) {
                        $contenido .= $this->formatearTextoPhpWord($hijo);
                    } elseif ($hijo instanceof TextBreak) {
                        $contenido .= '<br>';
                    }
                }
                $align = method_exists($elemento->getParagraphStyle(), 'getAlignment') ? (string) $elemento->getParagraphStyle()->getAlignment() : '';
                $style = $align !== '' ? ' style="text-align: '.strtolower($align).';"' : '';
                $html .= '<p'.$style.'>'.$contenido.'</p>';
            } elseif ($elemento instanceof Text) {
                $align = method_exists($elemento->getParagraphStyle(), 'getAlignment') ? (string) $elemento->getParagraphStyle()->getAlignment() : '';
                $style = $align !== '' ? ' style="text-align: '.strtolower($align).';"' : '';
                $html .= '<p'.$style.'>'.$this->formatearTextoPhpWord($elemento).'</p>';
            } elseif ($elemento instanceof TextBreak) {
                $html .= '<p><br></p>';
            }
        }

        return $html;
    }

    private function formatearTextoPhpWord(Text $texto): string
    {
        $contenido = htmlspecialchars($texto->getText(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $estilo = $texto->getFontStyle();
        if ($estilo === null || is_string($estilo)) {
            return $contenido;
        }

        $bold = method_exists($estilo, 'isBold') && $estilo->isBold();
        $italic = method_exists($estilo, 'isItalic') && $estilo->isItalic();
        $underline = method_exists($estilo, 'getUnderline') && $estilo->getUnderline() !== null && $estilo->getUnderline() !== 'none';
        $strike = method_exists($estilo, 'isStrikethrough') && $estilo->isStrikethrough();

        if ($bold) $contenido = '<strong>'.$contenido.'</strong>';
        if ($italic) $contenido = '<em>'.$contenido.'</em>';
        if ($underline) $contenido = '<u>'.$contenido.'</u>';
        if ($strike) $contenido = '<s>'.$contenido.'</s>';

        return $contenido;
    }

    /**
     * Post-procesa el HTML para consolidar spans contiguos idénticos y normalizar saltos de línea.
     */
    private function postProcesarHtml(string $html): string
    {
        // Consolidar spans contiguos con estilo idéntico
        $html = preg_replace_callback(
            '/<span style="([^"]+)">([^<]+)<\/span>\s*<span style="\1">([^<]+)<\/span>/u',
            static fn (array $m): string => '<span style="'.$m[1].'">'.$m[2].$m[3].'</span>',
            $html,
        ) ?? $html;

        // Repetir una vez para spans de 3+ contiguos
        $html = preg_replace_callback(
            '/<span style="([^"]+)">([^<]+)<\/span>\s*<span style="\1">([^<]+)<\/span>/u',
            static fn (array $m): string => '<span style="'.$m[1].'">'.$m[2].$m[3].'</span>',
            $html,
        ) ?? $html;

        // Colapsar múltiples párrafos vacíos consecutivos (>1) a uno solo
        $html = preg_replace('/(?:<p[^>]*><br\s*\/?><\/p>\s*){2,}/ui', '<p><br></p>', $html) ?? $html;

        // Eliminar párrafos vacíos al inicio del documento
        $html = preg_replace('/^(?:\s*<p[^>]*><br\s*\/?><\/p>\s*)+/ui', '', $html) ?? $html;

        return trim($html);
    }

    /**
     * @param array<string, string> $estilos
     */
    private function formatearEstilos(array $estilos): string
    {
        $partes = [];
        foreach ($estilos as $propiedad => $valor) {
            $partes[] = $propiedad.': '.$valor;
        }

        return implode('; ', $partes);
    }
}

