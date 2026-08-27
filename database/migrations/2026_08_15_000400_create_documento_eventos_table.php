<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_eventos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evento', 30)->index();
            $table->json('datos')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['documento_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_eventos');
    }
};
