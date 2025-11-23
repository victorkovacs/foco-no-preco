<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nome da tabela igual ao teu SQL original: LogIaTokensSimples
        Schema::create('LogIaTokensSimples', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_organizacao'); // Para separar por empresa
            $table->integer('tokens_in')->default(0);
            $table->integer('tokens_out')->default(0);
            $table->string('modelo', 50)->nullable(); // Ex: gpt-4, gpt-3.5
            $table->timestamp('data_registro')->useCurrent();

            // Índices para o gráfico ficar rápido
            $table->index(['id_organizacao', 'data_registro']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('LogIaTokensSimples');
    }
};
