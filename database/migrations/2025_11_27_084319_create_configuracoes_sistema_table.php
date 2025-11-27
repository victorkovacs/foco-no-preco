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
            $table->string('chave')->primary(); // Ex: 'horario_scraping'
            $table->string('valor');            // Ex: '01:00'
            $table->string('descricao')->nullable();
            $table->string('tipo')->default('text'); // text, number, time (para ajudar no input do html)
            $table->timestamps();
        });

        // Popula com os padrões que você definiu
        DB::table('configuracoes_sistema')->insert([
            [
                'chave' => 'horario_scraping',
                'valor' => '01:00',
                'descricao' => 'Horário da Coleta de Preços (Scraping)',
                'tipo' => 'time',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'chave' => 'horario_backup',
                'valor' => '00:01',
                'descricao' => 'Horário do Backup Diário',
                'tipo' => 'time',
                'created_at' => now(),
                'updated_at' => now()
            ],
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
