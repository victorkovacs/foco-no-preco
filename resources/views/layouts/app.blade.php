<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <meta name="sentry-dsn" content="{{ config('sentry.dsn') }}">

    <title>Anhanguera Ferramentas - @yield('title', 'Painel')</title>
    
    <link rel="icon" type="image/png" href="https://anhangueraferramentas.fbitsstatic.net/sf/img/favicon/apple-touch-icon.png?theme=main&v=202510311402">
    
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
        <h1 class="text-xl font-semibold text-gray-800">Anhanguera Tools</h1>
    </header>

    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-primary-dark text-white z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 flex flex-col">
        
        <div class="bg-white border-b border-gray-200 flex items-center justify-center h-16 shrink-0 p-2">
            <img src="https://anhangueraferramentas.fbitsstatic.net/sf/img/logo.svg?theme=main&v=202510311402" alt="Logo Anhanguera" class="h-10">
        </div>

        <nav class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
            
            @php 
                $user = Auth::user();
                // Usa os helpers definidos no Model User (Passo 1)
                $isMaster = $user->isMaster();      // Nível 1
                $isAdmin  = $user->isAdmin();       // Nível 1, 2
                $canEdit  = $user->canEdit();       // Nível 1, 2, 3
            @endphp

            {{-- 1. VISUALIZAÇÃO GERAL (Todos os Níveis) --}}
            <a href="{{ route('dashboard') }}" 
               class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('dashboard') || request()->routeIs('dashboard.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3"></i>
                <span class="font-medium">Dashboard</span>
            </a>

            <a href="{{ route('produtos.index') }}" 
               class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('produtos.index') ? 'nav-item-active' : 'nav-item-inactive' }}">
                <i data-lucide="package" class="w-5 h-5 mr-3"></i>
                <span class="font-medium">Produtos</span>
            </a>

            {{-- 2. OPERACIONAL / CADASTRO (Nível 3+) --}}
            @if($canEdit)
                <div class="mt-4 px-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Operacional</div>

                {{-- Gestão Avançada de Produtos --}}
                <div x-data="{ open: {{ request()->routeIs('produtos.gerenciar') || request()->routeIs('curadoria.*') || request()->routeIs('ia_manual.*') ? 'true' : 'false' }} }">
                    <button type="button" 
                            @click="open = !open" 
                            class="w-full flex items-center justify-between p-3 rounded-r-lg nav-item-inactive focus:outline-none group text-left transition-colors">
                        <div class="flex items-center">
                            <i data-lucide="database" class="w-5 h-5 mr-3"></i>
                            <span class="font-medium">Gestão DB</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                    </button>
                    
                    <div x-show="open" x-transition class="pl-6 mt-1 space-y-1 text-sm border-l border-white/10 ml-4">
                        <a href="{{ route('produtos.gerenciar') }}" class="block p-2 rounded text-gray-400 hover:text-white hover:bg-white/5 {{ request()->routeIs('produtos.gerenciar') ? 'text-white font-semibold' : '' }}">
                            Edição dos produtos
                        </a>
                        <a href="{{ route('curadoria.index') }}" class="block p-2 rounded text-gray-400 hover:text-white hover:bg-white/5 {{ request()->routeIs('curadoria.*') ? 'text-white font-semibold' : '' }}">
                            Curadoria
                        </a>
                        <a href="{{ route('ia_manual.index') }}" class="block p-2 rounded text-gray-400 hover:text-white hover:bg-white/5 {{ request()->routeIs('ia_manual.*') ? 'text-white font-semibold' : '' }}">
                            Revalidar IA Manual
                        </a>
                    </div>
                </div>

                <a href="{{ route('produtos_dashboard.index') }}" 
                   class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('produtos_dashboard.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <i data-lucide="file-text" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Cadastro Produto</span>
                </a>
            @endif


            {{-- 3. ADMINISTRAÇÃO (Nível 2+) --}}
            @if($isAdmin)
                <div class="mt-4 px-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Administração</div>

                <a href="{{ route('users.index') }}" 
                   class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('users.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <i data-lucide="users" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Equipe</span>
                </a>

                <a href="{{ route('concorrentes.index') }}" 
                   class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('concorrentes.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <i data-lucide="store" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Concorrentes</span>
                </a>

                {{-- Submenu Infra --}}
                <div x-data="{ open: {{ request()->routeIs('infra.*') || request()->routeIs('dlq.*') || request()->routeIs('templates_ia.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-3 rounded-r-lg nav-item-inactive group">
                        <div class="flex items-center">
                            <i data-lucide="server" class="w-5 h-5 mr-3"></i>
                            <span class="font-medium">Sistema</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="pl-6 mt-1 space-y-1 text-sm border-l border-white/10 ml-4">
                        <a href="{{ route('infra.index') }}" class="block p-2 rounded text-gray-400 hover:text-white {{ request()->routeIs('infra.*') ? 'text-white' : '' }}">Infraestrutura</a>
                        <a href="{{ route('dlq.index') }}" class="block p-2 rounded text-gray-400 hover:text-red-400 {{ request()->routeIs('dlq.*') ? 'text-red-400' : '' }}">Erros (DLQ)</a>
                        <a href="{{ route('templates_ia.index') }}" class="block p-2 rounded text-gray-400 hover:text-white {{ request()->routeIs('templates_ia.*') ? 'text-white' : '' }}">Templates IA</a>
                    </div>
                </div>
            @endif


            {{-- 4. MESTRE / DONO (Nível 1) --}}
            @if($isMaster)
                <div class="mt-4 px-3 text-xs font-bold text-yellow-500 uppercase tracking-wider">Master</div>

                <a href="{{ route('admin.configuracoes.index') }}" 
                   class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('admin.configuracoes.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <i data-lucide="settings-2" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Config. Globais</span>
                </a>

                <a href="{{ route('custos_ia.index') }}" 
                   class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('custos_ia.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <i data-lucide="dollar-sign" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Custos IA</span>
                </a>
            @endif


            {{-- CONFIGURAÇÕES (Comum a todos) --}}
            <div class="mt-4 border-t border-white/10 pt-2">
                <a href="{{ route('profile.password.edit') }}" 
                   class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('profile.password.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <i data-lucide="lock" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Minha Senha</span>
                </a>
            </div>

        </nav>

        {{-- Footer User Info --}}
        <div class="p-4 border-t border-gray-700 bg-[#002244] shrink-0">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-white/10 flex items-center justify-center text-white font-bold shrink-0">
                    {{ substr($user->email, 0, 1) }}
                </div>
                <div class="overflow-hidden flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate text-white" title="{{ $user->email }}">
                        {{ $user->email }}
                    </p>
                    <p class="text-xs flex items-center gap-1 text-gray-300">
                        @if($isMaster) <span class="text-yellow-400 font-bold">MESTRE</span>
                        @elseif($isAdmin) <span class="text-blue-300 font-bold">ADMIN</span>
                        @elseif($canEdit) <span class="text-green-300">CADASTRO</span>
                        @else <span class="text-gray-400">USUÁRIO</span>
                        @endif
                    </p>
                </div>
                
                <button onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
                        class="text-gray-400 hover:text-red-400 transition-colors p-1" 
                        title="Sair">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                </button>
            </div>
        </div>

    </aside>

    <div class="flex-1 flex flex-col min-h-screen lg:pl-64 transition-all duration-300"> 
        <main class="flex-1 p-6 lg:p-8 mt-16 lg:mt-0">
            @yield('content')
        </main>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <script>
        lucide.createIcons();

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('-translate-x-full');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                overlay.classList.add('opacity-0', 'pointer-events-none');
            } else {
                overlay.classList.remove('opacity-0', 'pointer-events-none');
            }
        }

        // Auto Logout por Inatividade (10 min)
        (function() {
            let inactivityTimer;
            const timeoutDuration = 600000; 

            function logoutUser() {
                const form = document.getElementById('logout-form');
                if (form) form.submit();
                else window.location.href = '/login';
            }

            function resetTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(logoutUser, timeoutDuration);
            }

            window.onload = resetTimer;
            document.onmousemove = resetTimer;
            document.onkeypress = resetTimer;
            document.onclick = resetTimer;
            document.onscroll = resetTimer;
        })();
    </script>
</body>
</html>