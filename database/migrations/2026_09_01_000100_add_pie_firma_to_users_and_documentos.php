<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('cargo', 150)->nullable()->after('role');
            $table->string('empresa', 150)->nullable()->after('cargo');
            $table->string('telefono', 50)->nullable()->after('empresa');
        });

        Schema::table('documentos', function (Blueprint $table): void {
            $table->foreignId('firmante_id')->nullable()->after('destinatario')->constrained('users')->nullOnDelete();
            $table->json('datos_firmante')->nullable()->after('firmante_id');
        });
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table): void {
            $table->dropForeign(['firmante_id']);
            $table->dropColumn(['firmante_id', 'datos_firmante']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['cargo', 'empresa', 'telefono']);
        });
    }
};
