<?php

declare(strict_types=1);

return [
    'documentos' => [
        'disk' => env('SDAYA_DOCUMENTOS_DISK', 'local'),
        'directorio' => env('SDAYA_DOCUMENTOS_PATH', 'documentos'),
    ],
    'marca' => [
        'nombre' => 'SDAYA',
        'razon' => 'Sistemas, Desarrollo de Aplicaciones y Auditoría',
        'lugar' => env('SDAYA_MARCA_LUGAR', 'La Paz'),
        'membrete' => public_path('images/brand/membrete-sdaya.png'),
        'logo' => public_path('images/brand/logo-sdaya.png'),
    ],
];
