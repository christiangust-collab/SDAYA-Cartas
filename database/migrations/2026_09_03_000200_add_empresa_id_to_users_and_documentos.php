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
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('empresa_id')->nullable()->after('role')->constrained('empresas')->nullOnDelete();
        });

        Schema::table('documentos', function (Blueprint $table): void {
            $table->foreignId('empresa_id')->nullable()->after('tipo_id')->constrained('empresas')->nullOnDelete();
            $table->json('datos_empresa')->nullable()->after('empresa_id');
        });

        // Migración de datos existentes: asociar a la empresa institucional por defecto
        $empresaDefault = DB::table('empresas')->where('nombre', 'SDAYA S.R.L.')->first()
            ?? DB::table('empresas')->first();

        if ($empresaDefault) {
            DB::table('users')->whereNull('empresa_id')->update([
                'empresa_id' => $empresaDefault->id,
            ]);

            DB::table('documentos')->whereNull('empresa_id')->update([
                'empresa_id' => $empresaDefault->id,
                'datos_empresa' => json_encode([
                    'id' => $empresaDefault->id,
                    'nombre' => $empresaDefault->nombre,
                    'nit' => $empresaDefault->nit,
                    'direccion' => $empresaDefault->direccion,
                    'telefono' => $empresaDefault->telefono,
                    'correo' => $empresaDefault->correo,
                    'sitio_web' => $empresaDefault->sitio_web,
                    'logo' => $empresaDefault->logo,
                ]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table): void {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn(['empresa_id', 'datos_empresa']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn(['empresa_id']);
        });
    }
};