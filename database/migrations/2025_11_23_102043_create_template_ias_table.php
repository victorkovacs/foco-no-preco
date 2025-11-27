<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates_ia', function (Blueprint $table) {
            $table->id();
            // ✅ ADICIONADO: Coluna de Organização
            $table->unsignedBigInteger('id_organizacao')->nullable()->index();

            $table->string('nome_template');
            $table->text('prompt_sistema')->nullable();
            $table->text('json_schema_saida')->nullable();
            $table->boolean('ativo')->default(1);
            $table->timestamps();
        });

        // Adiciona coluna em Produtos se não existir
        if (!Schema::hasColumn('Produtos', 'id_template_ia')) {
            Schema::table('Produtos', function (Blueprint $table) {
                $table->unsignedBigInteger('id_template_ia')->nullable()->after('id_organizacao');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('templates_ia');
        if (Schema::hasColumn('Produtos', 'id_template_ia')) {
            Schema::table('Produtos', function (Blueprint $table) {
                $table->dropColumn('id_template_ia');
            });
        }
    }
};
