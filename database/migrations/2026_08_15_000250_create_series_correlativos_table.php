<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series_correlativos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
            $table->foreignId('tipo_id')->constrained('tipos')->restrictOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->unsignedInteger('ultimo_correlativo')->default(0);
            $table->timestampsTz();

            $table->unique(
                ['area_id', 'tipo_id', 'anio'],
                'series_correlativos_area_tipo_anio_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series_correlativos');
    }
};
