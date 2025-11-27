<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cria a tabela templates_ia
        Schema::create('templates_ia', function (Blueprint $table) {
            $table->id(); // ID do template (padrão)

            // --- CORREÇÃO DO RELACIONAMENTO ---
            // Definimos manualmente porque sua tabela não segue o padrão 'id'
            $table->unsignedBigInteger('id_organizacao');

            $table->foreign('id_organizacao')
                ->references('id_organizacao') // Nome da PK na tabela pai
                ->on('Organizacoes')           // Nome EXATO da tabela pai (Maiúsculo)
                ->onDelete('cascade');

            $table->string('nome_template');
            $table->text('prompt_sistema')->nullable();
            $table->text('json_schema_saida')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // 2. Adiciona a coluna na tabela Produtos (Se existir)
        // Nota: Mantive 'Produtos' com P maiúsculo seguindo seu padrão
        if (Schema::hasTable('Produtos')) {
            if (!Schema::hasColumn('Produtos', 'id_template_ia')) {
                Schema::table('Produtos', function (Blueprint $table) {
                    $table->foreignId('id_template_ia')
                        ->nullable()
                        ->after('id_organizacao')
                        ->constrained('templates_ia')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        // Remove relacionamento em Produtos
        if (Schema::hasTable('Produtos') && Schema::hasColumn('Produtos', 'id_template_ia')) {
            Schema::table('Produtos', function (Blueprint $table) {
                $table->dropForeign(['id_template_ia']);
                $table->dropColumn('id_template_ia');
            });
        }

        Schema::dropIfExists('templates_ia');
    }
};
