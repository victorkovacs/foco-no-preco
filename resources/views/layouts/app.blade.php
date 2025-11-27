<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="sentry-dsn" content="{{ config('sentry.dsn') }}">

    <title>Foco no Preço - @yield('title', 'Painel')</title>
    
    <link rel="icon" type="image/png" href="https://anhangueraferramentas.fbitsstatic.net/sf/img/favicon/apple-touch-icon.png">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="flex min-h-screen bg-gray-100 font-sans">

    {{-- Overlay para Mobile --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 opacity-0 pointer-events-none transition-opacity z-40 lg:hidden" onclick="toggleSidebar()"></div>

    {{-- Header Mobile --}}
    <header class="bg-white shadow-md p-4 flex items-center fixed top-0 left-0 w-full z-30 lg:hidden h-16">
        <button class="text-gray-700 hover:text-highlight mr-4" onclick="toggleSidebar()">
            <i data-lucide="menu"></i>
        </button>
        <h1 class="text-xl font-semibold text-gray-800">Foco no Preço</h1>
    </header>

    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-[#002244] text-white z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 flex flex-col shadow-2xl">
        
        {{-- Logo --}}
        <div class="bg-white border-b border-gray-200 flex items-center justify-center h-16 shrink-0 p-2">
            <span class="text-[#002244] font-bold text-xl">Foco no Preço</span>
        </div>

        <nav class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
            
            @php 
                $user = Auth::user();
                $isMaster = $user->isMaster();      // Nível 1
                $isAdmin  = $user->isAdmin();       // Nível 1, 2
                $canEdit  = $user->canEdit();       // Nível 1, 2, 3
            @endphp

            {{-- ================================================= --}}
            {{-- 1. VISUALIZAÇÃO GERAL (Todos os Níveis)          --}}
            {{-- ================================================= --}}
            <a href="{{ route('dashboard') }}" 
               class="flex items-center p-3 rounded-lg hover:bg-white/10 transition-colors {{ request()->routeIs('dashboard') ? 'bg-white/20 text-white font-bold' : 'text-gray-300' }}">
                <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3"></i>
                <span class="font-medium">Dashboard</span>
            </a>

            <a href="{{ route('produtos.index') }}" 
               class="flex items-center p-3 rounded-lg hover:bg-white/10 transition-colors {{ request()->routeIs('produtos.index') ? 'bg-white/20 text-white font-bold' : 'text-gray-300' }}">
                <i data-lucide="package" class="w-5 h-5 mr-3"></i>
                <span class="font-medium">Produtos</span>
            </a>

            {{-- ================================================= --}}
            {{-- 2. OPERACIONAL (Nível 3 ou superior)             --}}
            {{-- ================================================= --}}
            @if($canEdit)
                <div class="mt-6 mb-2 px-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Operacional</div>

                {{-- Dropdown Gestão DB --}}
                <div x-data="{ open: {{ request()->routeIs('produtos.gerenciar') || request()->routeIs('curadoria.*') || request()->routeIs('ia_manual.*') ? 'true' : 'false' }} }">
                    <button type="button" 
                            @click="open = !open" 
                            class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-white/10 text-gray-300 focus:outline-none transition-colors">
                        <div class="flex items-center">
                            <i data-lucide="database" class="w-5 h-5 mr-3"></i>
                            <span class="font-medium">Gestão DB</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                    </button>
                    
                    <div x-show="open" x-transition class="mt-1 space-y-1 ml-4 border-l border-white/20 pl-2">
                        <a href="{{ route('produtos.gerenciar') }}" class="block p-2 rounded text-sm hover:text-white {{ request()->routeIs('produtos.gerenciar') ? 'text-white font-bold' : 'text-gray-400' }}">
                            Edição Rápida
                        </a>
                        <a href="{{ route('curadoria.index') }}" class="block p-2 rounded text-sm hover:text-white {{ request()->routeIs('curadoria.*') ? 'text-white font-bold' : 'text-gray-400' }}">
                            Curadoria
                        </a>
                        <a href="{{ route('ia_manual.index') }}" class="block p-2 rounded text-sm hover:text-white {{ request()->routeIs('ia_manual.*') ? 'text-white font-bold' : 'text-gray-400' }}">
                            IA Manual
                        </a>
                    </div>
                </div>

                <a href="{{ route('produtos_dashboard.index') }}" 
                   class="flex items-center p-3 rounded-lg hover:bg-white/10 transition-colors {{ request()->routeIs('produtos_dashboard.*') ? 'bg-white/20 text-white font-bold' : 'text-gray-300' }}">
                    <i data-lucide="file-plus" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Cadastrar Produto</span>
                </a>
            @endif


            {{-- ================================================= --}}
            {{-- 3. ADMINISTRAÇÃO (Nível 2 ou superior)           --}}
            {{-- ================================================= --}}
            @if($isAdmin)
                <div class="mt-6 mb-2 px-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Administração</div>

                <a href="{{ route('users.index') }}" 
                   class="flex items-center p-3 rounded-lg hover:bg-white/10 transition-colors {{ request()->routeIs('users.*') ? 'bg-white/20 text-white font-bold' : 'text-gray-300' }}">
                    <i data-lucide="users" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Equipe</span>
                </a>

                <a href="{{ route('concorrentes.index') }}" 
                   class="flex items-center p-3 rounded-lg hover:bg-white/10 transition-colors {{ request()->routeIs('concorrentes.*') ? 'bg-white/20 text-white font-bold' : 'text-gray-300' }}">
                    <i data-lucide="store" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Concorrentes</span>
                </a>

                {{-- Dropdown Sistema --}}
                <div x-data="{ open: {{ request()->routeIs('infra.*') || request()->routeIs('dlq.*') || request()->routeIs('templates_ia.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-white/10 text-gray-300 focus:outline-none transition-colors">
                        <div class="flex items-center">
                            <i data-lucide="server" class="w-5 h-5 mr-3"></i>
                            <span class="font-medium">Sistema</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="mt-1 space-y-1 ml-4 border-l border-white/20 pl-2">
                        <a href="{{ route('infra.index') }}" class="block p-2 rounded text-sm hover:text-white {{ request()->routeIs('infra.*') ? 'text-white' : 'text-gray-400' }}">Infraestrutura</a>
                        <a href="{{ route('dlq.index') }}" class="block p-2 rounded text-sm hover:text-red-400 {{ request()->routeIs('dlq.*') ? 'text-red-400 font-bold' : 'text-gray-400' }}">Erros (DLQ)</a>
                        <a href="{{ route('templates_ia.index') }}" class="block p-2 rounded text-sm hover:text-white {{ request()->routeIs('templates_ia.*') ? 'text-white font-bold' : 'text-gray-400' }}">Templates IA</a>
                    </div>
                </div>
            @endif


            {{-- ================================================= --}}
            {{-- 4. MASTER (Apenas Nível 1)                       --}}
            {{-- ================================================= --}}
            @if($isMaster)
                <div class="mt-6 mb-2 px-3 text-xs font-bold text-yellow-500 uppercase tracking-wider">Master</div>

                <a href="{{ route('admin.configuracoes.index') }}" 
                   class="flex items-center p-3 rounded-lg hover:bg-white/10 transition-colors {{ request()->routeIs('admin.configuracoes.*') ? 'bg-white/20 text-white font-bold' : 'text-gray-300' }}">
                    <i data-lucide="settings-2" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Config. Globais</span>
                </a>

                <a href="{{ route('custos_ia.index') }}" 
                   class="flex items-center p-3 rounded-lg hover:bg-white/10 transition-colors {{ request()->routeIs('custos_ia.*') ? 'bg-white/20 text-white font-bold' : 'text-gray-300' }}">
                    <i data-lucide="dollar-sign" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Custos IA</span>
                </a>
            @endif

            {{-- Configurações Pessoais (Para todos) --}}
            <div class="mt-6 border-t border-white/10 pt-4">
                <a href="{{ route('profile.password.edit') }}" 
                   class="flex items-center p-3 rounded-lg hover:bg-white/10 transition-colors {{ request()->routeIs('profile.password.*') ? 'bg-white/20 text-white font-bold' : 'text-gray-300' }}">
                    <i data-lucide="lock" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Alterar Senha</span>
                </a>
            </div>

        </nav>

        {{-- Footer User Info --}}
        <div class="p-4 bg-[#001a33] border-t border-white/10">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold shrink-0">
                    {{ strtoupper(substr($user->email, 0, 1)) }}
                </div>
                <div class="overflow-hidden flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate text-white" title="{{ $user->email }}">
                        {{ $user->email }}
                    </p>
                    <p class="text-xs flex items-center gap-1 text-gray-400">
                        @if($isMaster) <span class="text-yellow-400 font-bold">MESTRE</span>
                        @elseif($isAdmin) <span class="text-blue-400 font-bold">ADMIN</span>
                        @elseif($canEdit) <span class="text-green-400 font-bold">OPERADOR</span>
                        @else <span class="text-gray-500 font-bold">VISITANTE</span>
                        @endif
                    </p>
                </div>
                
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-red-400 transition-colors p-1" title="Sair">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    <div class="flex-1 flex flex-col min-h-screen lg:pl-64 transition-all duration-300"> 
        <main class="flex-1 p-6 lg:p-8 mt-16 lg:mt-0">
            @yield('content')
        </main>
    </div>

    <script>
        lucide.createIcons();
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            const overlay = document.getElementById('sidebar-overlay');
            if(document.getElementById('sidebar').classList.contains('-translate-x-full')) {
                overlay.classList.add('opacity-0', 'pointer-events-none');
            } else {
                overlay.classList.remove('opacity-0', 'pointer-events-none');
            }
        }
    </script>
</body>
</html>