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
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('nombre_comercial', 150)->nullable()->after('nombre');
            $table->string('nombre_aplicacion', 150)->default('Gestión de Cartas')->after('nombre_comercial');
            $table->string('color_principal', 25)->default('#002b49')->after('sitio_web');
            $table->string('color_secundario', 25)->default('#00487a')->after('color_principal');
            $table->string('favicon', 255)->nullable()->after('color_secundario');
            $table->string('logo_documentos', 255)->nullable()->after('logo');
            $table->boolean('es_predeterminada')->default(false)->after('activo');
        });

        $primeraEmpresa = DB::table('empresas')->orderBy('id')->first();
        if ($primeraEmpresa) {
            $esSdaya = str_contains(strtoupper((string) $primeraEmpresa->nombre), 'SDAYA');
            DB::table('empresas')->where('id', $primeraEmpresa->id)->update([
                'nombre_comercial' => $esSdaya ? 'SDAYA' : $primeraEmpresa->nombre,
                'nombre_aplicacion' => $esSdaya ? 'SDAYA Cartas' : 'Gestión de Cartas',
                'color_principal' => $esSdaya ? '#172944' : '#002b49',
                'color_secundario' => $esSdaya ? '#4766a9' : '#00487a',
                'es_predeterminada' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'nombre_comercial',
                'nombre_aplicacion',
                'color_principal',
                'color_secundario',
                'favicon',
                'logo_documentos',
                'es_predeterminada',
            ]);
        });
    }
};