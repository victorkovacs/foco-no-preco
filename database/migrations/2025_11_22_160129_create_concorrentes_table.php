<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('concorrentes', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('id_organizacao');
            
            // Outras colunas de chaves estrangeiras (opcional adicionar chaves reais)
            $table->integer('id_alvo')->nullable();
            $table->integer('id_link_externo')->nullable();

            $table->string('sku', 50)->nullable();
            
            // CORREÇÃO AQUI: Mudado de 'vendedor' (string) para 'ID_Vendedor' (int)
            // Para bater certo com o teu SQL oficial e com o comando do dashboard.
            $table->integer('ID_Vendedor')->nullable(); 
            
            $table->decimal('preco', 10, 2)->nullable();
            $table->dateTime('data_extracao');
            
            // Índices para performance
            $table->index(['id_organizacao', 'data_extracao']);
            $table->index(['id_organizacao', 'sku', 'data_extracao']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('concorrentes');
    }
};