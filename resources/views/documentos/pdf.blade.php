<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 108px 60px 95px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #1f2937; font-family: 'DejaVu Sans', sans-serif; font-size: 10.5pt; line-height: 1.55; }

        @font-face { font-family: 'Montserrat'; src: url("{{ public_path('fonts/Montserrat-Regular.ttf') }}"); font-weight: normal; font-style: normal; }
        @font-face { font-family: 'Montserrat'; src: url("{{ public_path('fonts/Montserrat-Bold.ttf') }}"); font-weight: bold; font-style: normal; }
        @font-face { font-family: 'Montserrat'; src: url("{{ public_path('fonts/Montserrat-Italic.ttf') }}"); font-weight: normal; font-style: italic; }
        @font-face { font-family: 'Montserrat'; src: url("{{ public_path('fonts/Montserrat-BoldItalic.ttf') }}"); font-weight: bold; font-style: italic; }

        .membrete { position: fixed; z-index: -10; top: -108px; right: -60px; bottom: -95px; left: -60px; width: 816px; height: 1056px; }
        .meta { margin-bottom: 26px; text-align: {{ $alineacionEncabezado ?? 'right' }}; }
        .cite { color: #293d61; font-weight: bold; }
        .contenido p { margin: 0 0 10px; }
        .contenido ul, .contenido ol { margin: 0 0 10px 20px; }
        .contenido img { max-width: 100%; height: auto; }
        .contenido figure.image { margin: 10px 0; text-align: center; }
        .contenido figure.image.image-style-align-left { text-align: left; }
        .contenido figure.image.image-style-align-right { text-align: right; }
        .contenido figure.image.image-style-align-center { text-align: center; }
        .contenido table { border-collapse: collapse; width: 100%; margin: 12px 0; }
        .contenido table td, .contenido table th { border: 1px solid #cbd5e1; padding: 6px 10px; vertical-align: top; }
        .contenido table th { background-color: #f1f5f9; font-weight: bold; color: #1e293b; }
        .contenido hr { border: 0; border-top: 1px solid #94a3b8; margin: 14px 0; }
        .contenido { position: relative; }
        .contenido img.ql-flotante { position: absolute; z-index: -1; }
        .contenido .ql-align-center, .contenido .text-align-center, .contenido .sdaya-align-center { text-align: center; }
        .contenido .ql-align-right, .contenido .text-align-right, .contenido .sdaya-align-right { text-align: right; }
        .contenido .ql-align-justify, .contenido .text-align-justify { text-align: justify; }
        .contenido .text-align-left, .contenido .sdaya-align-left { text-align: left; }
        .contenido .ql-font-montserrat { font-family: 'Montserrat', 'DejaVu Sans', sans-serif; }
        .contenido .ql-font-arial { font-family: 'DejaVu Sans', Arial, sans-serif; }
        .contenido .ql-font-times { font-family: 'DejaVu Serif', 'Times New Roman', serif; }
        .contenido .ql-font-georgia { font-family: 'DejaVu Serif', Georgia, serif; }
        .contenido .ql-font-courier { font-family: 'DejaVu Sans Mono', 'Courier New', monospace; }
        .contenido .ql-font-gothic { font-family: 'DejaVu Sans', 'Century Gothic', sans-serif; }
        .contenido .ql-size-10, .contenido .text-small { font-size: 10pt; }
        .contenido .ql-size-11 { font-size: 11pt; }
        .contenido .ql-size-12 { font-size: 12pt; }
        .contenido .ql-size-14, .contenido .text-big { font-size: 14pt; }
        .contenido .ql-size-16 { font-size: 16pt; }
        .contenido .ql-size-18, .contenido .text-huge { font-size: 18pt; }
        .contenido .ql-size-20 { font-size: 20pt; }
        .contenido .ql-size-24 { font-size: 24pt; }
        .contenido .sdaya-meta { margin-bottom: 22px; }
        .contenido .sdaya-meta-fecha { margin-bottom: 3px; }
        .contenido .sdaya-meta-cite { color: #293d61; font-weight: bold; margin-bottom: 0; }
        .contenido .sdaya-qr { margin: 24px 0; page-break-inside: avoid; text-align: center; }
        .contenido .sdaya-qr.sdaya-align-left { text-align: left; }
        .contenido .sdaya-qr.sdaya-align-right { text-align: right; }
        .contenido .sdaya-qr.sdaya-align-center { text-align: center; }
        .contenido .sdaya-qr img { width: 90px; height: 90px; }
        .contenido .sdaya-qr p { margin: 4px 0 0; color: #4766a9; font-size: 7.5pt; }
        .verificacion { margin-top: 30px; text-align: center; page-break-inside: avoid; }
        .verificacion img { width: 90px; height: 90px; }
        .verificacion p { margin: 5px auto 0; color: #4766a9; font-size: 7.5pt; }
        .token { max-width: 440px; word-break: break-all; color: #64748b !important; font-family: monospace; font-size: 6.5pt !important; }
    </style>
</head>
<body>
    @if ($membreteDataUri)
        <img src="{{ $membreteDataUri }}" class="membrete" alt="">
    @endif

    @if (! str_contains($contenidoDocumento, 'data-sdaya-meta'))
        <div class="meta">
            <div>{{ $lugarDocumento }}, {{ $documento->fecha_documento->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</div>
            <div class="cite">CITE: {{ $documento->cite }}</div>
        </div>
    @endif

    <div class="contenido">{!! $contenidoDocumento !!}</div>

    @php
        $pieFirma = $documento->pieFirma();
        $firmaDataUri = $documento->firmaDataUri();
        $alineacionPiePdf = match($documento->alineacion_pie_firma ?? $documento->alineacion_encabezado ?? 'right') {
            'left'   => 'left',
            'center' => 'center',
            default  => 'right',
        };
    @endphp
    @if ($pieFirma && (!empty($pieFirma['nombre']) || !empty($pieFirma['cargo'])))
        <div class="pie-firma" style="margin-top: 28px; text-align: {{ $alineacionPiePdf }}; page-break-inside: avoid; line-height: 1.25;">
            @if ($firmaDataUri)
                <div style="margin-bottom: -10px;">
                    <img src="{{ $firmaDataUri }}" alt="Firma" style="max-height: 75px; max-width: 200px; display: inline-block;">
                </div>
            @endif
            <div style="font-weight: bold; font-size: 10.5pt; color: #0f172a; margin-bottom: 2px;">{{ $pieFirma['nombre'] }}</div>
            @if (!empty($pieFirma['cargo']))
                <div style="font-weight: bold; font-size: 9.5pt; color: #0f172a; text-transform: uppercase; margin-bottom: 2px;">{{ $pieFirma['cargo'] }}</div>
            @endif
            @if (!empty($pieFirma['empresa']))
                <div style="font-weight: bold; font-size: 8.5pt; color: #0f172a; text-transform: uppercase; margin-bottom: 2px;">{{ $pieFirma['empresa'] }}</div>
            @endif
            @if (!empty($pieFirma['telefono']))
                <div style="font-size: 9pt; color: #0f172a; margin-bottom: 1px;"><span style="font-weight: bold;">móvil:</span> {{ $pieFirma['telefono'] }}</div>
            @endif
            @if (!empty($pieFirma['correo']))
                <div style="font-size: 9pt; color: #0f172a;"><span style="font-weight: bold;">email:</span> {{ $pieFirma['correo'] }}</div>
            @endif
        </div>
    @endif

    @if (! str_contains($contenidoDocumento, 'data-sdaya-qr'))
        <div class="verificacion">
            <img src="{{ $qrDataUri }}" alt="Código QR de verificación">
            <p>Escanea para verificar la autenticidad de este documento.</p>
            <p class="token">{{ $documento->hash_verificacion }}</p>
        </div>
    @endif
</body>
</html>
