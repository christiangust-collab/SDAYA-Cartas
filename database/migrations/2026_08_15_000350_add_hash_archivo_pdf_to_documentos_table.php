<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('documentos', 'hash_archivo_pdf')) {
            Schema::table('documentos', function (Blueprint $table): void {
                $table->string('hash_archivo_pdf', 64)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('documentos', 'hash_archivo_pdf')) {
            Schema::table('documentos', function (Blueprint $table): void {
                $table->dropColumn('hash_archivo_pdf');
            });
        }
    }
};
