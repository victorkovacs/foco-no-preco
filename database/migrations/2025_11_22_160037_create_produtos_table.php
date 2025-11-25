<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Produtos', function (Blueprint $table) {
            // ID (PK)
            $table->id('ID'); // No teu SQL é ID maiúsculo

            // FK Organizacao
            $table->unsignedBigInteger('id_organizacao');
            $table->foreign('id_organizacao')->references('id_organizacao')->on('Organizacoes')->onDelete('cascade');

            $table->string('SKU', 100);
            $table->string('Nome', 255)->nullable();
            $table->text('LinkPesquisa')->nullable();
            $table->text('LinkMeuSite')->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('Categoria', 100)->nullable();
            $table->string('SubCategoria', 100)->nullable();

            // ⚠️ COLUNA 'PrecoVenda' REMOVIDA

            $table->boolean('EncontrouConcorrentes')->default(0);
            $table->boolean('ativo')->default(0); // Notei que no SQL é 'ativo' minúsculo aqui
            $table->boolean('ia_processado')->default(0);

            // Índice único por organização
            $table->unique(['id_organizacao', 'SKU'], 'uk_org_sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Produtos');
    }
};
