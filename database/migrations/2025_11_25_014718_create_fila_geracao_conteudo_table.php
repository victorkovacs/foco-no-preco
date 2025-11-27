<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('FilaGeracaoConteudo');

        Schema::create('FilaGeracaoConteudo', function (Blueprint $table) {
            $table->id();

            // ✅ ADICIONADO: Coluna de Organização
            $table->unsignedBigInteger('id_organizacao')->nullable()->index();

            // DADOS
            $table->string('sku', 100)->index();
            $table->string('nome_produto', 255);
            $table->string('palavra_chave_entrada', 255);

            // RELACIONAMENTO
            $table->unsignedBigInteger('id_template_ia');
            $table->foreign('id_template_ia')->references('id')->on('templates_ia');

            $table->unsignedBigInteger('id_produto')->index();

            // CONTROLE
            $table->string('status', 50)->default('pendente')->index();
            $table->text('mensagem_erro')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('FilaGeracaoConteudo');
    }
};
