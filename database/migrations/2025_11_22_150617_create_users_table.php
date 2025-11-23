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
        // Cria a tabela 'Usuarios' (Respeitando o teu SQL antigo)
        Schema::create('Usuarios', function (Blueprint $table) {
            $table->id(); // id

            // FK para Organizacoes (CRÍTICO: Organizacoes tem de existir antes)
            $table->unsignedBigInteger('id_organizacao');
            $table->foreign('id_organizacao', 'fk_usuario_organizacao')
                  ->references('id_organizacao')
                  ->on('Organizacoes')
                  ->onDelete('cascade');

            $table->string('email', 100)->unique();
            $table->string('senha_hash', 255); // A tua coluna de senha
            $table->timestamp('data_criacao')->useCurrent(); // O teu timestamp
            $table->integer('nivel_acesso')->nullable();
            $table->boolean('ativo')->default(1);
            $table->string('api_key', 100)->nullable();
        });

        // Tabelas de suporte do Laravel (Recomendado manter)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Usuarios');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};