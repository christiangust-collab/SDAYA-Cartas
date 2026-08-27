<?php

declare(strict_types=1);

use App\Http\Controllers\AnularDocumentoController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BuscarVerificacionController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CorrelativoPreviewController;
use App\Http\Controllers\DescargarDocumentoController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\EmitirDocumentoController;
use App\Http\Controllers\ImportarDocxController;
use App\Http\Controllers\PdfPublicoController;
use App\Http\Controllers\QrDocumentoController;
use App\Http\Controllers\TipoController;
use App\Http\Controllers\VerificacionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'inicio')->name('inicio');

Route::post('/verificar', BuscarVerificacionController::class)
    ->middleware('throttle:verificacion')
    ->name('verificar.buscar');
Route::get('/verificar/{hash}', VerificacionController::class)
    ->middleware('throttle:verificacion')
    ->name('verificar.show');
Route::get('/verificar/{hash}/pdf', PdfPublicoController::class)
    ->middleware('throttle:verificacion')
    ->where('hash', '[A-Fa-f0-9]{64}')
    ->name('verificar.pdf');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/olvide-mi-contrasena', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/olvide-mi-contrasena', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/restablecer-contrasena', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/panel', fn () => redirect()->route('documentos.index'))->name('panel');

    Route::get('/api/correlativo-preview', CorrelativoPreviewController::class)
        ->middleware('throttle:120,1')
        ->name('api.correlativo-preview');

    Route::resource('documentos', DocumentoController::class)->except(['destroy']);
    Route::post('/documentos/{documento}/emitir', EmitirDocumentoController::class)
        ->name('documentos.emitir');
    Route::post('/documentos/{documento}/anular', AnularDocumentoController::class)
        ->name('documentos.anular');
    Route::get('/documentos/{documento}/descargar/{formato}', DescargarDocumentoController::class)
        ->whereIn('formato', ['pdf', 'docx'])
        ->name('documentos.descargar');
    Route::get('/documentos/{documento}/qr', QrDocumentoController::class)
        ->name('documentos.qr');

    Route::post('/documentos/importar-docx', ImportarDocxController::class)
        ->middleware('throttle:30,1')
        ->name('documentos.importar-docx');

    Route::prefix('catalogos')->name('catalogos.')->group(function (): void {
        Route::get('/', CatalogoController::class)->name('index');

        Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
        Route::put('/areas/{area}', [AreaController::class, 'update'])->name('areas.update');
        Route::patch('/areas/{area}/estado', [AreaController::class, 'toggle'])->name('areas.toggle');

        Route::post('/tipos', [TipoController::class, 'store'])->name('tipos.store');
        Route::put('/tipos/{tipo}', [TipoController::class, 'update'])->name('tipos.update');
        Route::patch('/tipos/{tipo}/estado', [TipoController::class, 'toggle'])->name('tipos.toggle');
    });
});
