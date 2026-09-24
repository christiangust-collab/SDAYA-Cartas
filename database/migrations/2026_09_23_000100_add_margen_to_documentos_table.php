<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos', function (Blueprint $table): void {
            if (! Schema::hasColumn('documentos', 'margenes')) {
                $table->string('margenes', 60)->default('3.0,2.5,3.0,3.0')->after('alineacion_pie_firma');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table): void {
            if (Schema::hasColumn('documentos', 'margenes')) {
                $table->dropColumn('margenes');
            }
        });
    }
};
