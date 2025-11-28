<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SystemHealthController extends Controller
{
    public function check(Request $request)
    {
        $user = Auth::user();

        // 1. Coleta de Métricas de Fila
        try {
            $queueSize = Redis::llen('celery');
            $dlqSize   = Redis::llen('fila_dlq_erros');
        } catch (\Exception $e) {
            $queueSize = -1;
            $dlqSize   = -1;
        }

        // 2. Data da Última Atividade
        $lastActivityDate = null;
        if ($user) {
            $lastActivityDate = DB::table('concorrentes')
                ->where('id_organizacao', $user->id_organizacao)
                ->max('data_extracao');
        }

        $lastUpdateCarbon = $lastActivityDate ? Carbon::parse($lastActivityDate)->locale('pt_BR') : null;
        $textoTempo = $lastUpdateCarbon ? $lastUpdateCarbon->diffForHumans() : 'Nenhuma coleta recente';

        // 3. Coleta de Infra
        $systemMem = $this->getSystemMemory();
        $systemCpu = sys_getloadavg();
        $cpuLoad   = isset($systemCpu[0]) ? $systemCpu[0] : 0;
        $cpuCount  = $this->getCpuCount();

        // 4. Status Global
        $statusGlobal = 'operacional';

        // [MUDANÇA AQUI] Agora só fica laranja se passar de 24 HORAS sem dados
        if ($lastUpdateCarbon && $lastUpdateCarbon->diffInHours(now()) > 20) {
            $statusGlobal = 'degradado';
        }

        if ($queueSize === -1) {
            $statusGlobal = 'erro';
        }

        // 5. Montagem da Resposta
        $response = [
            'status' => $statusGlobal,
            'texto_tempo' => $textoTempo,
        ];

        // Métricas Administrativas
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            $response['admin_metrics'] = [
                'fila_celery' => $queueSize,
                'fila_dlq'    => $dlqSize,
                'memoria_php' => $this->formatBytes(memory_get_usage(true)),
                'server_ram'  => $systemMem['percent'],
                'server_cpu'  => $cpuLoad,
                'cpu_cores'   => $cpuCount,
            ];
        }

        return response()->json($response);
    }

    // --- Métodos Auxiliares ---

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
        return 1;
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
                return ['percent' => round(($used / $total) * 100)];
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

    public function index()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }
        return view('admin.infra.index');
    }
}
