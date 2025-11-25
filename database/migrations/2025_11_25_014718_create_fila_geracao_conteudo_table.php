<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Se a tabela já existir (de testes anteriores), apagamos ela para recriar limpa
        Schema::dropIfExists('FilaGeracaoConteudo');

        Schema::create('FilaGeracaoConteudo', function (Blueprint $table) {
            $table->id();

            // DADOS INDEPENDENTES (Cópia fiel do momento que você gerou a tarefa)
            $table->string('sku', 100)->index();
            $table->string('nome_produto', 255);
            $table->string('palavra_chave_entrada', 255); // O prompt base

            // RELACIONAMENTO (Apenas com Templates, como pedido)
            $table->unsignedBigInteger('id_template_ia');
            $table->foreign('id_template_ia')->references('id')->on('templates_ia');

            // Mantemos o id_produto apenas como referência numérica para o robô saber onde salvar o resultado final depois,
            // mas SEM chave estrangeira (foreign key) prendendo a tabela.
            $table->unsignedBigInteger('id_produto')->index();

            // CONTROLE
            $table->string('status', 50)->default('pendente')->index(); // pendente, processando, concluido, erro
            $table->text('mensagem_erro')->nullable();

            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('FilaGeracaoConteudo');
    }
};
