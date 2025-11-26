<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- --- NOVO: Configuração do Sentry --- --}}
    <meta name="sentry-dsn" content="{{ env('SENTRY_LARAVEL_DSN') }}">

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
                // Helpers baseados no Model User (certifique-se que isAdmin e isColaborador existem lá)
                $isAdmin = $user->isAdmin(); 
                $isColaborador = $user->isColaborador();
                // Mantendo lógica para o Nível 3 (Redator/Freela) caso não tenha helper
                $isRedator = $user->nivel_acesso == 3;
            @endphp

            {{-- 1. DASHBOARD (Admin e Colaborador) --}}
            @if($isAdmin || $isColaborador)
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('dashboard') || request()->routeIs('dashboard.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
            @endif

            {{-- 2. MEUS PRODUTOS (Admin e Colaborador) --}}
            @if($isAdmin || $isColaborador)
                <a href="{{ route('produtos.index') }}" 
                   class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('produtos.index') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <i data-lucide="package" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Meus Produtos</span>
                </a>
            @endif

            {{-- 3. GESTÃO DB (Admin e Colaborador) --}}
            @if($isAdmin || $isColaborador)
                <div x-data="{ open: {{ request()->routeIs('produtos.gerenciar') || request()->routeIs('concorrentes.*') || request()->routeIs('curadoria.*') || request()->routeIs('custos_ia.*') || request()->routeIs('ia_manual.*') || request()->routeIs('users.*') ? 'true' : 'false' }} }">
                    
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
                        
                        {{-- Visível para Admin e Colaborador --}}
                        <a href="{{ route('produtos.gerenciar') }}" class="block p-2 rounded text-gray-400 hover:text-white hover:bg-white/5 {{ request()->routeIs('produtos.gerenciar') ? 'text-white font-semibold' : '' }}">
                            Edição dos produtos
                        </a>
                        
                        <a href="{{ route('concorrentes.index') }}" class="block p-2 rounded text-gray-400 hover:text-white hover:bg-white/5 {{ request()->routeIs('concorrentes.*') ? 'text-white font-semibold' : '' }}">
                            Gerencia Concorrentes
                        </a>

                        <a href="{{ route('curadoria.index') }}" class="block p-2 rounded text-gray-400 hover:text-white hover:bg-white/5 {{ request()->routeIs('curadoria.*') ? 'text-white font-semibold' : '' }}">
                            Curadoria
                        </a>

                        <a href="{{ route('ia_manual.index') }}" class="block p-2 rounded text-gray-400 hover:text-white hover:bg-white/5 {{ request()->routeIs('ia_manual.*') ? 'text-white font-semibold' : '' }}">
                            Revalidar IA Manual
                        </a>

                        {{-- EXCLUSIVO ADMIN --}}
                        @if($isAdmin)
                            <div class="pt-2 mt-2 border-t border-white/10">
                                <span class="text-xs text-gray-500 px-2 uppercase font-bold tracking-wider">Admin</span>
                                
                                <a href="{{ route('custos_ia.index') }}" class="block p-2 rounded text-gray-400 hover:text-white hover:bg-white/5 {{ request()->routeIs('custos_ia.*') ? 'text-white font-semibold' : '' }}">
                                    Custos de IA
                                </a>

                                <a href="{{ route('users.index') }}" class="block p-2 rounded text-gray-400 hover:text-white hover:bg-white/5 {{ request()->routeIs('users.*') ? 'text-white font-semibold' : '' }}">
                                    Gestão de Usuários
                                </a>
                            </div>
                        @endif
                        @if($isAdmin)
                            <div class="pt-2 mt-2 border-t border-white/10">
                                <span class="text-xs text-gray-500 px-2 uppercase font-bold tracking-wider">Admin System</span>
                                
                                <a href="{{ route('custos_ia.index') }}" class="...">...</a>

                                <a href="{{ route('dlq.index') }}" 
                                class="block p-2 rounded text-gray-400 hover:text-white hover:bg-white/5 {{ request()->routeIs('dlq.*') ? 'text-red-400 font-semibold bg-white/5' : '' }}">
                                    <div class="flex items-center">
                                        <i data-lucide="alert-triangle" class="w-4 h-4 mr-2"></i>
                                        Monitor de Erros
                                    </div>
                                </a>
                            </div>
                        @endif
                        @if($isAdmin)
                            <a href="{{ route('infra.index') }}"
                                class="flex items-center p-2 rounded text-gray-400 hover:text-white hover:bg-white/5 mb-1 {{ request()->routeIs('infra.index') ? 'text-green-400 font-semibold bg-white/5' : '' }}">
                                <i data-lucide="activity" class="w-4 h-4 mr-3 {{ request()->routeIs('infra.index') ? 'text-green-400' : 'text-green-600' }}"></i>
                                <span>Infraestrutura</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
            

            {{-- 4. CADASTRO PRODUTO (Admin e Nível 3/Redator) --}}
            {{-- Colaborador (0) NÃO vê isso na sua regra original, mantive assim --}}
            @if($isAdmin || $isRedator)
                <a href="{{ route('produtos_dashboard.index') }}" 
                   class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('produtos_dashboard.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <i data-lucide="file-text" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Cadastro Produto</span>
                </a>
            @endif

            {{-- 5. TEMPLATE I.A (Apenas Admin) --}}
            {{-- ATENÇÃO: Se o Nível 3 precisar disso, avise para mudarmos a rota web.php também --}}
            @if($isAdmin)
                <a href="{{ route('templates_ia.index') }}" 
                   class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('templates_ia.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <i data-lucide="cpu" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Template I.A</span>
                </a>
            @endif

            {{-- 6. CONFIGURAÇÕES (Todos) --}}
            <a href="{{ route('profile.password.edit') }}" 
               class="flex items-center p-3 rounded-r-lg {{ request()->routeIs('profile.password.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                <i data-lucide="settings" class="w-5 h-5 mr-3"></i>
                <span class="font-medium">Configurações</span>
            </a>

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
                        @if($isAdmin) <span class="text-yellow-400 font-bold">ADMIN</span>
                        @elseif($isColaborador) <span class="text-blue-300">Colaborador</span>
                        @else <span class="text-gray-400">Usuário</span>
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
            const timeoutDuration = 600000; // 10 minutos

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