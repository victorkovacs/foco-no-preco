<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Models\AlvoMonitoramento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SystemHealthController extends Controller
{
    public function check(Request $request)
    {
        // 1. Coleta de Métricas
        try {
            $queueSize = Redis::llen('celery');
            $dlqSize   = Redis::llen('fila_dlq_erros');
        } catch (\Exception $e) {
            $queueSize = -1;
            $dlqSize   = -1;
        }

        $lastActivityDate = AlvoMonitoramento::max('data_ultima_verificacao');
        $lastUpdateCarbon = $lastActivityDate ? Carbon::parse($lastActivityDate)->locale('pt_BR') : null;

        // 2. Coleta de Infra (Sem Chutes)
        $systemMem = $this->getSystemMemory();
        $systemCpu = sys_getloadavg();
        $cpuLoad   = isset($systemCpu[0]) ? $systemCpu[0] : 0;
        $cpuCount  = $this->getCpuCount(); // <--- DETECÇÃO AUTOMÁTICA DE NÚCLEOS

        // 3. Status Global
        $statusGlobal = 'operacional';

        if ($lastUpdateCarbon && $lastUpdateCarbon->diffInHours(now()) > 3) {
            $statusGlobal = 'degradado';
        }
        if ($queueSize === -1) {
            $statusGlobal = 'erro';
        }

        // 4. Resposta
        $response = [
            'status' => $statusGlobal,
            'texto_tempo' => $lastUpdateCarbon ? $lastUpdateCarbon->diffForHumans() : 'Aguardando dados...',
        ];

        $user = Auth::user();
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            $response['admin_metrics'] = [
                'fila_celery' => $queueSize,
                'fila_dlq'    => $dlqSize,
                'memoria_php' => $this->formatBytes(memory_get_usage(true)),
                'server_ram'  => $systemMem['percent'],
                'server_cpu'  => $cpuLoad,
                'cpu_cores'   => $cpuCount, // Enviamos o limite real para o front
            ];
        }

        return response()->json($response);
    }

    /**
     * Conta quantos núcleos de CPU o servidor possui
     */
    private function getCpuCount()
    {
        try {
            if (is_file('/proc/cpuinfo')) {
                $cpuinfo = file_get_contents('/proc/cpuinfo');
                preg_match_all('/^processor/m', $cpuinfo, $matches);
                $count = count($matches[0]);
                return $count > 0 ? $count : 1;
            }
        } catch (\Exception $e) {
        }
        return 1; // Padrão de segurança
    }

    private function getSystemMemory()
    {
        try {
            $memInfo = file_get_contents("/proc/meminfo");
            preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatches);
            preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availableMatches);

            if (isset($totalMatches[1]) && isset($availableMatches[1])) {
                $total = $totalMatches[1];
                $available = $availableMatches[1];
                $used = $total - $available;
                $percent = round(($used / $total) * 100);

                return ['percent' => $percent];
            }
        } catch (\Exception $e) {
        }
        return ['percent' => 0];
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
