<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega la columna alineacion_encabezado a la tabla documentos.
 *
 * Determina la alineación del bloque Lugar / Fecha / CITE en el PDF y el DOCX.
 * El valor por defecto 'right' mantiene el comportamiento histórico de todos
 * los documentos existentes sin necesidad de migración de datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos', function (Blueprint $table): void {
            $table->string('alineacion_encabezado', 10)->default('right')->after('lugar');
        });
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table): void {
            $table->dropColumn('alineacion_encabezado');
        });
    }
};
