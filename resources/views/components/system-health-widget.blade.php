<div id="health-widget-panel" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border-l-4 border-gray-300 transition-colors duration-500">
    <div class="p-4 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        
        <div class="flex items-center">
            <span class="relative flex h-3 w-3 mr-3">
              {{-- O Ping (Animação) --}}
              <span id="status-ping" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gray-400 opacity-75"></span>
              {{-- O Ponto Fixo --}}
              <span id="status-dot" class="relative inline-flex rounded-full h-3 w-3 bg-gray-500"></span>
            </span>
            <div>
                <h3 class="text-sm font-medium text-gray-900">Status do Monitoramento</h3>
                <p class="text-sm text-gray-500">
                    Última coleta: 
                    <span id="last-update-label" class="font-semibold text-gray-700">Carregando...</span>
                </p>
            </div>
        </div>

        <div id="admin-metrics-area" class="hidden w-full sm:w-auto bg-gray-50 rounded-md p-3 text-xs text-gray-600 font-mono border border-gray-200">
            <div class="grid grid-cols-2 sm:flex sm:flex-row gap-x-6 gap-y-2">
                
                <div title="Tarefas na fila">Fila: <strong id="metric-queue" class="text-indigo-600">--</strong></div>
                <div title="Erros na DLQ">DLQ: <strong id="metric-dlq" class="text-gray-600">--</strong></div>
                
                <div class="hidden sm:block border-l border-gray-300 mx-2"></div>
                
                <div title="Uso de RAM">
                    RAM: <span id="metric-ram" class="font-bold">--</span>%
                </div>
                <div title="Load Average / Núcleos Disponíveis">
                    CPU: <span id="metric-cpu" class="font-bold">--</span> <span class="text-gray-400">/</span> <span id="metric-cores">--</span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const endpoint = "{{ route('api.health_check') }}";
        
        // [CORREÇÃO] Mapeamento explícito de classes para o Tailwind não remover (PurgeCSS)
        const statusConfig = {
            'operacional': {
                panel: 'border-green-500',
                dot:   'bg-green-500',
                ping:  'bg-green-400'
            },
            'degradado': {
                panel: 'border-yellow-500',
                dot:   'bg-yellow-500',
                ping:  'bg-yellow-400'
            },
            'erro': {
                panel: 'border-red-500',
                dot:   'bg-red-500',
                ping:  'bg-red-400'
            },
            'default': {
                panel: 'border-gray-300',
                dot:   'bg-gray-500',
                ping:  'bg-gray-400'
            }
        };

        const getColorClass = (value, warningThreshold, criticalThreshold) => {
            const num = parseFloat(value);
            if (num >= criticalThreshold) return 'text-red-600';
            if (num >= warningThreshold) return 'text-yellow-600';
            return 'text-green-600';
        };

        const updateWidget = async () => {
            try {
                // Timestamp para evitar cache do navegador
                const res = await fetch(endpoint + '?_t=' + new Date().getTime());
                const data = await res.json();

                // 1. Status Geral
                const panel = document.getElementById('health-widget-panel');
                const dot = document.getElementById('status-dot');
                const ping = document.getElementById('status-ping');
                
                // Seleciona as classes baseado no status (ou default se não existir)
                const config = statusConfig[data.status] || statusConfig['default'];

                // Aplica as classes
                panel.className = `bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border-l-4 transition-colors duration-500 ${config.panel}`;
                dot.className   = `relative inline-flex rounded-full h-3 w-3 ${config.dot}`;
                ping.className  = `animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 ${config.ping}`;

                document.getElementById('last-update-label').innerText = data.texto_tempo;

                // 2. Admin Metrics
                if (data.admin_metrics) {
                    document.getElementById('admin-metrics-area').classList.remove('hidden');
                    
                    document.getElementById('metric-queue').innerText = data.admin_metrics.fila_celery;
                    
                    const dlqEl = document.getElementById('metric-dlq');
                    dlqEl.innerText = data.admin_metrics.fila_dlq;
                    dlqEl.className = data.admin_metrics.fila_dlq > 0 ? 'text-red-600 font-bold animate-pulse' : 'text-green-600 font-bold';

                    // RAM
                    const ramVal = data.admin_metrics.server_ram;
                    const ramEl = document.getElementById('metric-ram');
                    ramEl.innerText = ramVal;
                    ramEl.className = "font-bold " + getColorClass(ramVal, 70, 90);

                    // CPU
                    const load = parseFloat(data.admin_metrics.server_cpu);
                    const cores = parseInt(data.admin_metrics.cpu_cores || 1);
                    
                    const cpuEl = document.getElementById('metric-cpu');
                    cpuEl.innerText = load.toFixed(2); 
                    
                    document.getElementById('metric-cores').innerText = cores + ' Cr';

                    if (load >= cores) {
                        cpuEl.className = 'font-bold text-red-600';
                    } else if (load >= (cores * 0.7)) {
                        cpuEl.className = 'font-bold text-yellow-600';
                    } else {
                        cpuEl.className = 'font-bold text-green-600';
                    }
                }
            } catch (err) { 
                console.error("Erro widget:", err); 
            }
        };

        updateWidget();
        setInterval(updateWidget, 15000);
    });
</script>