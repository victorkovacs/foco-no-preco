<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('Organizacoes', function (Blueprint $table) {
        // PK: id_organizacao
        $table->id('id_organizacao'); 

        $table->string('nome_empresa', 255);
        $table->string('cnpj_cpf', 18)->nullable()->unique('uk_cnpj_cpf');
        $table->string('api_key', 100)->nullable()->unique('uk_api_key');
        $table->string('plano', 50)->default('basic');

        // data_cadastro (timestamp)
        $table->timestamp('data_cadastro')->useCurrent();

        // ativa (tinyint 1)
        $table->boolean('ativa')->default(1);
    });
}

public function down(): void
{
    Schema::dropIfExists('Organizacoes');
}
};
