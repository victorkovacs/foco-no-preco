<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('Vendedores', function (Blueprint $table) {
        $table->id('ID_Vendedor');
        
        $table->unsignedBigInteger('id_organizacao');
        $table->foreign('id_organizacao')->references('id_organizacao')->on('Organizacoes')->onDelete('cascade');

        $table->string('NomeVendedor', 255);
        $table->string('SeletorPreco', 255)->nullable();
        $table->boolean('Ativo')->default(0);
        $table->decimal('PercentualDescontoAVista', 5, 2)->nullable();
        $table->string('SeletorMarca', 255)->nullable();
        $table->string('LinkConcorrente', 255)->nullable();
    });
}

public function down(): void
{
    Schema::dropIfExists('Vendedores');
}
};
