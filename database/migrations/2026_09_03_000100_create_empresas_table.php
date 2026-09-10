<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 150);
            $table->string('nit', 50)->nullable();
            $table->string('direccion', 250)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('sitio_web', 150)->nullable();
            $table->string('logo', 255)->nullable();
            $table->boolean('activo')->default(true)->index();
            $table->timestampsTz();
        });

        // Registro inicial institucional seguro para conservar compatibilidad
        DB::table('empresas')->insert([
            'nombre' => 'SDAYA S.R.L.',
            'nit' => '1028374029',
            'direccion' => 'Av. 20 de Octubre, Edif. Los Pinos, Piso 4, La Paz - Bolivia',
            'telefono' => '+591 2 2123456',
            'correo' => 'contacto@sdaya.com.bo',
            'sitio_web' => 'https://sdaya.com.bo',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};