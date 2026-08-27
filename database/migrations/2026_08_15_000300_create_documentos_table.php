<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table): void {
            $table->id();
            $table->string('cite', 80)->nullable()->unique();
            $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
            $table->foreignId('tipo_id')->constrained('tipos')->restrictOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->unsignedInteger('correlativo')->nullable();
            $table->date('fecha_documento');
            $table->string('asunto', 250);
            $table->string('destinatario', 250);
            $table->longText('contenido');
            $table->string('estado', 20)->default('borrador')->index();
            $table->string('hash_verificacion', 64)->nullable()->unique();
            $table->string('hash_contenido', 64)->nullable();
            $table->string('hash_archivo_pdf', 64)->nullable();
            $table->string('archivo_docx')->nullable();
            $table->string('archivo_pdf')->nullable();
            $table->foreignId('emitido_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('emitido_at')->nullable();
            $table->timestampsTz();

            $table->unique(
                ['area_id', 'tipo_id', 'anio', 'correlativo'],
                'documentos_area_tipo_anio_correlativo_unique'
            );
            $table->index(['area_id', 'tipo_id', 'anio', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
