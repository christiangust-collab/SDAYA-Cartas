<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap de pruebas
|--------------------------------------------------------------------------
|
| Dentro de Docker el contenedor exporta variables reales (APP_ENV=local,
| DB_CONNECTION=pgsql, SESSION_DRIVER=database, etc.). PHPUnit con force
| no sobrescribe $_SERVER, que es la fuente que Dotenv consulta primero.
| Aquí se fuerza el entorno de pruebas en las tres tiendas de variables
| antes de que la aplicación arranque.
|
*/

ini_set('memory_limit', '512M');

$variables = [
    'APP_ENV' => 'testing',
    'APP_KEY' => 'base64:3uJ2bIsqiZ7qH1kO5R3WkZ9oT8rY4vX1aB2cDeFgHiJ=',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS' => '4',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
];

foreach ($variables as $clave => $valor) {
    putenv($clave.'='.$valor);
    $_ENV[$clave] = $valor;
    $_SERVER[$clave] = $valor;
}

/*
 * En el contenedor no existe .env (está en .dockerignore); su ausencia genera
 * advertencias al ejecutar las pruebas con `php artisan test`.
 */
$archivoEntorno = __DIR__.'/../.env';

if (! is_file($archivoEntorno)) {
    @touch($archivoEntorno);
}

require __DIR__.'/../vendor/autoload.php';
