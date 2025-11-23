@extends('layouts.app')

@section('title', 'Gestão de Templates IA')

@section('content')
<div class="w-full max-w-7xl mx-auto">
    
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                <i data-lucide="cpu" class="mr-3 text-primary-dark"></i>
                Gestão de Templates (IA)
            </h1>
            <p class="text-gray-500 text-sm mt-1">Configure os prompts e esquemas JSON para a geração de conteúdo.</p>
        </div>
        <button onclick="abrirModal()" class="bg-primary-dark hover:bg-primary-darker text-white px-4 py-2 rounded-lg flex items-center shadow-md transition-colors">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Novo Template
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold">
                <tr>
                    <th class="p-4 w-16 text-center">ID</th>
                    <th class="p-4">Nome do Template</th>
                    <th class="p-4 w-32 text-center">Ativo</th>
                    <th class="p-4 w-32 text-center">Ações</th>
                </tr>
            </thead>
            <tbody id="tabela-body" class="divide-y divide-gray-100">
                <tr><td colspan="4" class="p-8 text-center text-gray-500">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="modal-template" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 transition-opacity duration-300 opacity-0">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl transform scale-95 transition-transform duration-300" id="modal-content">
        
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800" id="modal-titulo">Novo Template</h3>
            <button onclick="fecharModal()" class="text-gray-400 hover:text-red-500"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>

        <div class="p-6 space-y-4">
            <input type="hidden" id="template_id">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Template</label>
                <input type="text" id="nome_template" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark outline-none" placeholder="Ex: Descrição SEO Curta">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prompt do Sistema (Instruções para a IA)</label>
                <textarea id="prompt_sistema" rows="6" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark outline-none font-mono text-sm" placeholder="Você é um especialista em SEO..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Schema JSON de Saída (Opcional)</label>
                <textarea id="json_schema_saida" rows="4" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark outline-none font-mono text-sm text-blue-600" placeholder="{ &quot;type&quot;: &quot;object&quot;... }"></textarea>
            </div>
        </div>

        <div class="p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl flex justify-end gap-3">
            <button onclick="fecharModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-200 rounded-lg transition-colors">Cancelar</button>
            <button onclick="salvarTemplate()" class="px-4 py-2 bg-primary-dark text-white rounded-lg hover:bg-opacity-90 transition-colors shadow-sm">Salvar</button>
        </div>
    </div>
</div>

<script>
    // Carrega a lista ao abrir
    document.addEventListener('DOMContentLoaded', () => {
        carregarTemplates();
    });

    function carregarTemplates() {
        const tbody = document.getElementById('tabela-body');
        tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center"><i data-lucide="loader-2" class="w-6 h-6 animate-spin mx-auto text-primary-dark"></i></td></tr>';
        lucide.createIcons();

        fetch("{{ route('templates_ia.list') }}")
            .then(res => res.json())
            .then(data => {
                if(data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-500">Nenhum template cadastrado.</td></tr>';
                    return;
                }
                
                let html = '';
                data.forEach(t => {
                    html += `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-4 text-center text-gray-500 font-mono text-xs">${t.id}</td>
                            <td class="p-4 font-medium text-gray-800">${t.nome_template}</td>
                            <td class="p-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${t.ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${t.ativo ? 'Ativo' : 'Inativo'}
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <button onclick="editarTemplate(${t.id})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors"><i data-lucide="edit-2" class="w-4 h-4"></i></button>
                                    <button onclick="excluirTemplate(${t.id})" class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
                lucide.createIcons();
            });
    }

    // Modal Functions
    const modal = document.getElementById('modal-template');
    const content = document.getElementById('modal-content');

    function abrirModal(limpar = true) {
        if(limpar) {
            document.getElementById('template_id').value = '';
            document.getElementById('nome_template').value = '';
            document.getElementById('prompt_sistema').value = '';
            document.getElementById('json_schema_saida').value = '';
            document.getElementById('modal-titulo').innerText = 'Novo Template';
        }
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
        }, 10);
    }

    function fecharModal() {
        modal.classList.add('opacity-0');
        content.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    // API Operations
    function salvarTemplate() {
        const id = document.getElementById('template_id').value;
        const data = {
            id: id,
            nome_template: document.getElementById('nome_template').value,
            prompt_sistema: document.getElementById('prompt_sistema').value,
            json_schema_saida: document.getElementById('json_schema_saida').value
        };

        fetch("{{ route('templates_ia.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                fecharModal();
                carregarTemplates();
            } else {
                alert('Erro: ' + JSON.stringify(res.message));
            }
        });
    }

    function editarTemplate(id) {
        fetch(`{{ url('/templates-ia/show') }}/${id}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('template_id').value = data.id;
                document.getElementById('nome_template').value = data.nome_template;
                document.getElementById('prompt_sistema').value = data.prompt_sistema;
                document.getElementById('json_schema_saida').value = data.json_schema_saida;
                document.getElementById('modal-titulo').innerText = 'Editar Template';
                abrirModal(false);
            });
    }

    function excluirTemplate(id) {
        if(!confirm('Tem certeza que deseja excluir este template?')) return;

        fetch(`{{ url('/templates-ia') }}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" }
        })
        .then(() => carregarTemplates());
    }
</script>
@endsection