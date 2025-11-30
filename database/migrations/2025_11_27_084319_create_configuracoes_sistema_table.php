<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes_sistema', function (Blueprint $table) {
            $table->string('chave')->primary();
            $table->string('valor');
            $table->string('descricao')->nullable();
            $table->string('tipo')->default('text');
            $table->timestamps();
        });

        // Popula com os padrões
        DB::table('configuracoes_sistema')->insert([
            [
                'chave' => 'horario_scraping',
                'valor' => '01:00',
                'descricao' => 'Horário da Coleta de Preços (Scraping)',
                'tipo' => 'time',
                'created_at' => now(),
                'updated_at' => now()
            ],
            // --- NOVO ITEM ADICIONADO AQUI ---
            [
                'chave' => 'horario_envio_email',
                'valor' => '08:00',
                'descricao' => 'Horário do Relatório por E-mail',
                'tipo' => 'time',
                'created_at' => now(),
                'updated_at' => now()
            ],
            // ---------------------------------
            [
                'chave' => 'horario_backup',
                'valor' => '00:01',
                'descricao' => 'Horário do Backup Diário',
                'tipo' => 'time',
                'created_at' => now(),
                'updated_at' => now()
            ],
            // ---------------------------------
            [
                'chave' => 'horario_importacao_sitemap',
                'valor' => '04:00', // Padrão 04:00 da manhã
                'descricao' => 'Horário da Descoberta de Novos Links (Sitemap)',
                'tipo' => 'time',
                'created_at' => now(),
                'updated_at' => now()
            ],
            // ---------------------------------
            [
                'chave' => 'intervalo_dashboard_horas',
                'valor' => '3',
                'descricao' => 'Intervalo (horas) atualização Dashboard',
                'tipo' => 'number',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes_sistema');
    }
};
