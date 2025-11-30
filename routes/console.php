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
        // Busca configuração ou usa padrão
        $intervaloHoras = DB::table('configuracoes_sistema')
            ->where('chave', 'intervalo_dashboard_horas') // Nome corrigido conforme migration
            ->value('valor');

        $horas = (is_numeric($intervaloHoras) && $intervaloHoras > 0) ? (int)$intervaloHoras : 1;

        if ($horas == 1) {
            Schedule::command('dashboard:update')->hourly();
        } else {
            Schedule::command('dashboard:update')->cron("0 */{$horas} * * *");
        }

        // 2. RELATÓRIO DE E-MAIL (Horário Fixo Diário)
        // ----------------------------------------------------
        // Nota: Certifique-se de que a chave 'horario_envio_email' existe no banco ou adicione na migration
        $horarioEmail = DB::table('configuracoes_sistema')
            ->where('chave', 'horario_envio_email')
            ->value('valor');

        // Valida se é um horário válido (HH:MM), senão usa 08:00
        $horaEmailAgendada = preg_match('/^\d{2}:\d{2}$/', $horarioEmail ?? '') ? $horarioEmail : '08:00';

        Schedule::command('relatorio:diario')->dailyAt($horaEmailAgendada);

        // 3. IMPORTAÇÃO DE SITEMAP (Novos Links - Rotina Nova)
        // ----------------------------------------------------
        // Busca o horário específico desta rotina nova
        $horarioSitemap = DB::table('configuracoes_sistema')
            ->where('chave', 'horario_importacao_sitemap')
            ->value('valor');

        // Se não configurado, roda às 04:00 da manhã
        $horaSitemapAgendada = preg_match('/^\d{2}:\d{2}$/', $horarioSitemap ?? '') ? $horarioSitemap : '04:00';

        Schedule::command('sitemap:importar')
            ->dailyAt($horaSitemapAgendada)
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/sitemap_import.log'));

        // 4. BACKUP DIÁRIO (Exemplo se quiser usar a chave que já existe no banco)
        // ----------------------------------------------------
        $horarioBackup = DB::table('configuracoes_sistema')
            ->where('chave', 'horario_backup')
            ->value('valor');

        $horaBackupAgendada = preg_match('/^\d{2}:\d{2}$/', $horarioBackup ?? '') ? $horarioBackup : '00:01';

        // Se você tiver um comando de backup, agende aqui:
        // Schedule::command('backup:run')->dailyAt($horaBackupAgendada);
    }
} catch (\Exception $e) {
    Schedule::command('dashboard:update')->hourly();
    Schedule::command('relatorio:diario')->dailyAt('08:00');
    Schedule::command('sitemap:importar')->dailyAt('04:00');
}
