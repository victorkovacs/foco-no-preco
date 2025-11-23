<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela AlvosMonitoramento (Para Curadoria)
        Schema::create('AlvosMonitoramento', function (Blueprint $table) {
            $table->id('id_alvo');
            $table->unsignedBigInteger('id_organizacao');

            // Relacionamentos (Assumindo que Produtos e Vendedores já existem)
            $table->unsignedBigInteger('ID_Produto');
            $table->foreign('ID_Produto')->references('ID')->on('Produtos')->onDelete('cascade');

            // Nota: id_link_externo vamos ligar apenas logicamente se a tabela links_externos ainda não existir no momento da criação
            $table->integer('id_link_externo');

            $table->boolean('ativo')->default(1);
            $table->string('status_verificacao', 20)->default('OK');
            $table->dateTime('data_ultima_verificacao')->nullable();

            // Foreign Keys
            $table->foreign('id_organizacao')->references('id_organizacao')->on('Organizacoes')->onDelete('cascade');
        });

        // 2. Tabela links_externos
        Schema::create('links_externos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_organizacao');
            $table->foreign('id_organizacao')->references('id_organizacao')->on('Organizacoes')->onDelete('cascade');

            $table->string('SKU', 100)->nullable();
            $table->boolean('ativo')->default(1);
            $table->string('status_link', 20)->default('OK');
            $table->dateTime('data_ultima_verificacao')->nullable();
            $table->timestamp('data_cadastro')->useCurrent();

            $table->unsignedBigInteger('ID_Vendedor');
            $table->foreign('ID_Vendedor')->references('ID_Vendedor')->on('Vendedores')->onDelete('cascade');

            $table->text('nome')->nullable();
            $table->string('link', 500);

            // Índices únicos do teu SQL
            $table->unique(['id_organizacao', 'ID_Vendedor', 'link'], 'idx_link_unico');
        });

        // 3. Tabela conteudo_gerado_ia
        Schema::create('conteudo_gerado_ia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produto_id');
            $table->foreign('produto_id')->references('ID')->on('Produtos')->onDelete('cascade');

            $table->string('modelo_usado', 50)->nullable();
            $table->string('versao_prompt', 20)->default('v1');
            $table->json('conteudo_gerado_json');
            $table->timestamp('gerado_em')->useCurrent();
            $table->integer('id_template_ia')->nullable();
        });

        // 4. Tabela cache_api_google
        Schema::create('cache_api_google', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produto_id');
            $table->foreign('produto_id')->references('ID')->on('Produtos')->onDelete('cascade');

            $table->enum('tipo_busca', ['texto', 'imagem']);
            $table->json('resposta_bruta_json');
            $table->timestamp('buscado_em')->useCurrent();

            $table->unique(['produto_id', 'tipo_busca']);
        });

        // 5. Tabelas de Logs (Opcionais, mas recomendadas para evitar erros de SQL)
        Schema::create('LogOperacoes', function (Blueprint $table) {
            $table->id('LogID');
            $table->unsignedBigInteger('id_organizacao');
            $table->string('NomeScript', 255);
            $table->dateTime('DataHoraInicio');
            $table->dateTime('DataHoraFim')->nullable();
            $table->decimal('DuracaoSegundos', 10, 4)->nullable();
            $table->string('Status', 20);
            $table->text('MensagemErro')->nullable();
        });

        Schema::create('LogDetalhesSKU', function (Blueprint $table) {
            $table->id('DetalheID');
            $table->unsignedBigInteger('id_organizacao');
            $table->unsignedBigInteger('LogID_FK');
            $table->foreign('LogID_FK')->references('LogID')->on('LogOperacoes')->onDelete('cascade');

            $table->string('SKU', 50);
            $table->string('Status', 20);
        });

        Schema::create('logs_tarefas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produto_id');
            $table->enum('etapa', ['google_texto', 'google_imagem', 'openai_api', 'banco_dados']);
            $table->text('mensagem_erro')->nullable();
            $table->timestamp('data_hora')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_tarefas');
        Schema::dropIfExists('LogDetalhesSKU');
        Schema::dropIfExists('LogOperacoes');
        Schema::dropIfExists('cache_api_google');
        Schema::dropIfExists('conteudo_gerado_ia');
        Schema::dropIfExists('links_externos');
        Schema::dropIfExists('AlvosMonitoramento');
    }
};
