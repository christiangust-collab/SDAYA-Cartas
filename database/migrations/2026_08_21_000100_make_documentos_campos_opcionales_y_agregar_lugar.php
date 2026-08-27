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
            $table->string('asunto', 250)->nullable()->change();
            $table->string('destinatario', 250)->nullable()->change();
            $table->string('lugar', 80)->nullable()->after('fecha_documento');
        });
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table): void {
            $table->dropColumn('lugar');
            $table->string('destinatario', 250)->change();
            $table->string('asunto', 250)->change();
        });
    }
};
