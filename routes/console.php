<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- AGENDAMENTOS DINÂMICOS ---

try {
    if (Schema::hasTable('configuracoes_sistema')) {

        // 1. DASHBOARD (Intervalo em Horas)
        // ----------------------------------------------------
        $intervaloHoras = DB::table('configuracoes_sistema')
            ->where('chave', 'dashboard_intervalo_horas')
            ->value('valor');

        $horas = (is_numeric($intervaloHoras) && $intervaloHoras > 0) ? (int)$intervaloHoras : 1;

        if ($horas == 1) {
            Schedule::command('dashboard:update')->hourly();
        } else {
            Schedule::command('dashboard:update')->cron("0 */{$horas} * * *");
        }

        // 2. RELATÓRIO DE E-MAIL (Horário Fixo Diário)
        // ----------------------------------------------------
        $horarioEmail = DB::table('configuracoes_sistema')
            ->where('chave', 'horario_envio_email') // <--- Chave que vamos criar/usar
            ->value('valor');

        // Valida se é um horário válido (HH:MM), senão usa 08:00
        $horaAgendada = preg_match('/^\d{2}:\d{2}$/', $horarioEmail) ? $horarioEmail : '08:00';

        Schedule::command('relatorio:diario')->dailyAt($horaAgendada);
    }
} catch (\Exception $e) {
    // Fallback de segurança em caso de erro no banco
    Schedule::command('dashboard:update')->hourly();
    Schedule::command('relatorio:diario')->dailyAt('08:00');
}
